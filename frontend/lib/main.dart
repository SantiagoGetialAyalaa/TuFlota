import 'dart:async';
import 'dart:convert';
import 'dart:math' as math;

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

void main() {
  runApp(const FlotaApp());
}

String defaultApiBaseUrl() {
  if (kIsWeb) return 'http://127.0.0.1:8000/api';
  if (defaultTargetPlatform == TargetPlatform.android) {
    return 'http://10.0.2.2:8000/api';
  }
  return 'http://127.0.0.1:8000/api';
}

class FlotaApp extends StatelessWidget {
  const FlotaApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Flota',
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xff0f766e)),
        scaffoldBackgroundColor: const Color(0xfff6f8fb),
        inputDecorationTheme: const InputDecorationTheme(
          border: OutlineInputBorder(),
          isDense: true,
        ),
        cardTheme: const CardThemeData(
          elevation: 0,
          margin: EdgeInsets.zero,
          color: Colors.white,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.all(Radius.circular(8)),
            side: BorderSide(color: Color(0xffdbe3ef)),
          ),
        ),
      ),
      home: const HomeScreen(),
    );
  }
}

class ApiClient {
  ApiClient(this.baseUrl);

  String baseUrl;
  String? token;

  Uri _uri(String path, [Map<String, String?> query = const {}]) {
    final cleanBase = baseUrl.endsWith('/')
        ? baseUrl.substring(0, baseUrl.length - 1)
        : baseUrl;
    final filtered = <String, String>{};
    query.forEach((key, value) {
      if (value != null && value.trim().isNotEmpty) {
        filtered[key] = value.trim();
      }
    });
    return Uri.parse('$cleanBase$path').replace(queryParameters: filtered);
  }

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    if (token != null) 'Authorization': 'Bearer $token',
  };

  Future<dynamic> get(String path, [Map<String, String?> query = const {}]) {
    return _send(() => http.get(_uri(path, query), headers: _headers));
  }

  Future<dynamic> post(String path, Map<String, dynamic> body) {
    return _send(
      () => http.post(_uri(path), headers: _headers, body: jsonEncode(body)),
    );
  }

  Future<dynamic> delete(String path) {
    return _send(() => http.delete(_uri(path), headers: _headers));
  }

  Future<dynamic> _send(Future<http.Response> Function() request) async {
    final response = await request();
    final body = response.body.isEmpty ? null : jsonDecode(response.body);
    if (response.statusCode >= 200 && response.statusCode < 300) return body;
    final message = body is Map && body['message'] != null
        ? body['message'].toString()
        : 'Error ${response.statusCode}';
    throw ApiException(message);
  }
}

class ApiException implements Exception {
  ApiException(this.message);

  final String message;

  @override
  String toString() => message;
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late final ApiClient api;
  Timer? refreshTimer;

  final baseUrl = TextEditingController(text: defaultApiBaseUrl());
  final origin = TextEditingController();
  final destination = TextEditingController();
  final email = TextEditingController(text: 'test@example.com');
  final password = TextEditingController(text: 'password');
  final name = TextEditingController(text: 'Test User');
  final phone = TextEditingController(text: '3001234567');
  final routeOrigin = TextEditingController(text: 'Pasto');
  final routeDestination = TextEditingController(text: 'Tangua');
  final routePrice = TextEditingController(text: '8000');
  final routeDeparture = TextEditingController(text: '08:00');
  final routeArrival = TextEditingController(text: '09:00');
  final routeDistance = TextEditingController(text: '32.5');
  final routeMinutes = TextEditingController(text: '60');
  final scheduleId = TextEditingController(text: '1');
  final vehicleId = TextEditingController(text: '1');
  final driverId = TextEditingController(text: '1');

  bool busy = false;
  bool authIsCompany = false;
  String? notice;
  String? error;
  Map<String, dynamic>? user;
  List<dynamic> trips = [];
  List<dynamic> seats = [];
  List<dynamic> reservations = [];
  List<dynamic> companyRoutes = [];
  List<dynamic> passengers = [];
  dynamic selectedTrip;
  dynamic paidReservation;
  final selectedSeats = <int>{};

  @override
  void initState() {
    super.initState();
    api = ApiClient(baseUrl.text);
    searchTrips();
    refreshTimer = Timer.periodic(const Duration(seconds: 20), (_) {
      refreshCompanyData(quiet: true);
    });
  }

  @override
  void dispose() {
    refreshTimer?.cancel();
    for (final controller in [
      baseUrl,
      origin,
      destination,
      email,
      password,
      name,
      phone,
      routeOrigin,
      routeDestination,
      routePrice,
      routeDeparture,
      routeArrival,
      routeDistance,
      routeMinutes,
      scheduleId,
      vehicleId,
      driverId,
    ]) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> runTask(Future<void> Function() task) async {
    setState(() {
      busy = true;
      error = null;
      notice = null;
    });
    try {
      api.baseUrl = baseUrl.text.trim();
      await task();
    } on ApiException catch (exception) {
      setState(() => error = exception.message);
    } catch (exception) {
      setState(() => error = exception.toString());
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  int intFrom(TextEditingController controller, String label) {
    final value = int.tryParse(controller.text.trim());
    if (value == null) throw ApiException('$label debe ser numerico.');
    return value;
  }

  double doubleFrom(TextEditingController controller, String label) {
    final value = double.tryParse(controller.text.trim());
    if (value == null) throw ApiException('$label debe ser numerico.');
    return value;
  }

  Future<void> login() {
    return runTask(() async {
      final data = await api.post('/auth/login', {
        'email': email.text.trim(),
        'password': password.text,
      });
      api.token = data['token']?.toString();
      user = Map<String, dynamic>.from(data['user'] as Map);
      notice = 'Sesion iniciada';
      await afterAuthRefresh();
      setState(() {});
    });
  }

  Future<void> register() {
    return runTask(() async {
      await api.post('/auth/register', {
        'name': name.text.trim(),
        'email': email.text.trim(),
        'phone': phone.text.trim(),
        'role': authIsCompany ? 'company' : 'user',
        'password': password.text,
      });
      notice = 'Cuenta creada';
      await login();
    });
  }

  Future<void> afterAuthRefresh() async {
    if (user?['role'] == 'company') {
      await refreshCompanyData(quiet: true);
    } else if (user != null) {
      await loadReservations(quiet: true);
    }
  }

  Future<void> searchTrips() {
    return runTask(() async {
      final data = await api.get('/trips', {
        'origin': origin.text,
        'destination': destination.text,
      });
      trips = data as List<dynamic>;
      if (selectedTrip != null) {
        selectedTrip = _findTripById(selectedTrip['id']);
      }
      notice = trips.isEmpty
          ? 'No hay rutas disponibles'
          : '${trips.length} ruta(s) encontrada(s)';
      setState(() {});
    });
  }

  dynamic _findTripById(dynamic id) {
    for (final trip in trips) {
      if (trip is Map && trip['id'] == id) return trip;
    }
    return null;
  }

  Future<void> selectTrip(dynamic trip) {
    return runTask(() async {
      selectedTrip = trip;
      selectedSeats.clear();
      paidReservation = null;
      seats = await api.get('/seats/trips/${trip['id']}') as List<dynamic>;
      notice = 'Selecciona tu asiento';
      setState(() {});
    });
  }

  Future<void> reserveAndPay() {
    return runTask(() async {
      if (user == null) {
        throw ApiException('Inicia sesion o crea una cuenta para reservar.');
      }
      if (selectedTrip == null || selectedSeats.isEmpty) {
        throw ApiException('Selecciona un viaje y al menos un asiento.');
      }

      final reservation = await api.post('/reservations', {
        'user_id': user!['id'],
        'trip_id': selectedTrip['id'],
        'seat_ids': selectedSeats.toList(),
        'reserved_minutes': 15,
      });
      paidReservation = await api.post(
        '/reservations/${reservation['id']}/pay',
        {},
      );
      notice = 'Pago aprobado';
      await selectTrip(selectedTrip);
      await loadReservations(quiet: true);
      await refreshCompanyData(quiet: true);
      setState(() {});
    });
  }

  Future<void> loadReservations({bool quiet = false}) {
    return runTask(() async {
      if (user == null) return;
      reservations =
          await api.get('/users/${user!['id']}/reservations') as List<dynamic>;
      if (!quiet) notice = '${reservations.length} reserva(s)';
      setState(() {});
    });
  }

  Future<void> createRoute() {
    return runTask(() async {
      final data = await api.post('/company/routes', {
        'origin': routeOrigin.text.trim(),
        'destination': routeDestination.text.trim(),
        'base_price': doubleFrom(routePrice, 'Precio'),
        'departure_time': routeDeparture.text.trim(),
        'estimated_arrival_time': routeArrival.text.trim(),
        'distance_km': doubleFrom(routeDistance, 'Distancia'),
        'estimated_duration_minutes': intFrom(routeMinutes, 'Duracion'),
      });
      final schedule = data['schedule'] as Map;
      scheduleId.text = schedule['id'].toString();
      notice = 'Ruta creada. Horario #${schedule['id']}';
      await refreshCompanyData(quiet: true);
      setState(() {});
    });
  }

  Future<void> createTrip() {
    return runTask(() async {
      final data = await api.post('/trips', {
        'schedule_id': intFrom(scheduleId, 'Horario'),
        'vehicle_id': intFrom(vehicleId, 'Vehiculo'),
        'driver_id': intFrom(driverId, 'Conductor'),
        'departure_datetime': DateTime.now()
            .add(const Duration(days: 1))
            .toIso8601String(),
        'status': 'scheduled',
      });
      notice = 'Viaje #${data['id']} creado';
      await searchTrips();
      await refreshCompanyData(quiet: true);
    });
  }

  Future<void> refreshCompanyData({bool quiet = false}) {
    return runTask(() async {
      companyRoutes = await api.get('/company/routes') as List<dynamic>;
      passengers = await api.get('/company/passengers') as List<dynamic>;
      if (!quiet) notice = 'Modulo empresa actualizado';
      setState(() {});
    });
  }

  bool get isCompany => user?['role'] == 'company';
  bool get isUser => user?['role'] == 'user' || user == null;

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: isCompany ? 2 : 1,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Flota'),
          actions: [
            if (user == null)
              TextButton.icon(
                onPressed: () => showAuthSheet(context),
                icon: const Icon(Icons.login),
                label: const Text('Ingresar'),
              )
            else
              TextButton.icon(
                onPressed: () {
                  setState(() {
                    user = null;
                    api.token = null;
                    reservations = [];
                  });
                },
                icon: const Icon(Icons.logout),
                label: Text(user!['name'].toString()),
              ),
          ],
          bottom: isCompany
              ? const TabBar(
                  tabs: [
                    Tab(icon: Icon(Icons.storefront), text: 'Empresa'),
                    Tab(icon: Icon(Icons.search), text: 'Cliente'),
                  ],
                )
              : null,
        ),
        body: SafeArea(
          child: Column(
            children: [
              _TopBar(
                baseUrl: baseUrl,
                busy: busy,
                notice: notice,
                error: error,
                user: user,
              ),
              Expanded(
                child: isCompany
                    ? TabBarView(
                        children: [
                          _CompanyView(
                            routes: companyRoutes,
                            trips: trips,
                            passengers: passengers,
                            scheduleId: scheduleId,
                            vehicleId: vehicleId,
                            driverId: driverId,
                            routeOrigin: routeOrigin,
                            routeDestination: routeDestination,
                            routePrice: routePrice,
                            routeDeparture: routeDeparture,
                            routeArrival: routeArrival,
                            routeDistance: routeDistance,
                            routeMinutes: routeMinutes,
                            onCreateRoute: createRoute,
                            onCreateTrip: createTrip,
                            onRefresh: refreshCompanyData,
                            onUseSchedule: (id) {
                              setState(() => scheduleId.text = id.toString());
                            },
                          ),
                          customerView(),
                        ],
                      )
                    : customerView(),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget customerView() {
    return _CustomerView(
      origin: origin,
      destination: destination,
      trips: trips,
      selectedTrip: selectedTrip,
      seats: seats,
      selectedSeats: selectedSeats,
      user: user,
      reservations: reservations,
      paidReservation: paidReservation,
      onSearch: searchTrips,
      onSelectTrip: selectTrip,
      onSeatChanged: (id, selected) {
        setState(() {
          selected ? selectedSeats.add(id) : selectedSeats.remove(id);
        });
      },
      onAuth: () => showAuthSheet(context),
      onPay: reserveAndPay,
      onLoadReservations: loadReservations,
    );
  }

  void showAuthSheet(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (context) => StatefulBuilder(
        builder: (context, sheetSetState) {
          return Padding(
            padding: EdgeInsets.only(
              left: 16,
              right: 16,
              bottom: MediaQuery.of(context).viewInsets.bottom + 16,
            ),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 560),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  SegmentedButton<bool>(
                    segments: const [
                      ButtonSegment(
                        value: false,
                        label: Text('Usuario'),
                        icon: Icon(Icons.person),
                      ),
                      ButtonSegment(
                        value: true,
                        label: Text('Empresa'),
                        icon: Icon(Icons.business),
                      ),
                    ],
                    selected: {authIsCompany},
                    onSelectionChanged: (value) {
                      sheetSetState(() => authIsCompany = value.first);
                    },
                  ),
                  const SizedBox(height: 12),
                  _Field(
                    controller: name,
                    label: authIsCompany ? 'Nombre empresa' : 'Nombre',
                    icon: Icons.badge,
                  ),
                  _Field(controller: email, label: 'Email', icon: Icons.email),
                  _Field(
                    controller: phone,
                    label: 'Telefono',
                    icon: Icons.phone,
                  ),
                  _Field(
                    controller: password,
                    label: 'Password',
                    icon: Icons.key,
                    obscureText: true,
                  ),
                  _Actions(
                    children: [
                      FilledButton.icon(
                        onPressed: () {
                          Navigator.pop(context);
                          login();
                        },
                        icon: const Icon(Icons.login),
                        label: const Text('Iniciar sesion'),
                      ),
                      OutlinedButton.icon(
                        onPressed: () {
                          Navigator.pop(context);
                          register();
                        },
                        icon: const Icon(Icons.person_add),
                        label: const Text('Crear cuenta'),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _TopBar extends StatelessWidget {
  const _TopBar({
    required this.baseUrl,
    required this.busy,
    required this.notice,
    required this.error,
    required this.user,
  });

  final TextEditingController baseUrl;
  final bool busy;
  final String? notice;
  final String? error;
  final Map<String, dynamic>? user;

  @override
  Widget build(BuildContext context) {
    final message = error ?? notice;
    final messageColor = error == null ? const Color(0xff0f766e) : Colors.red;

    return Material(
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            Expanded(
              flex: 2,
              child: TextField(
                controller: baseUrl,
                decoration: const InputDecoration(
                  labelText: 'API',
                  prefixIcon: Icon(Icons.link),
                ),
              ),
            ),
            const SizedBox(width: 10),
            _Pill(
              icon: user?['role'] == 'company' ? Icons.business : Icons.person,
              text: user == null ? 'Invitado' : '${user!['name']}',
            ),
            if (message != null) ...[
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  message,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(color: messageColor),
                ),
              ),
            ],
            if (busy) ...[
              const SizedBox(width: 10),
              const SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(strokeWidth: 2),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _CustomerView extends StatelessWidget {
  const _CustomerView({
    required this.origin,
    required this.destination,
    required this.trips,
    required this.selectedTrip,
    required this.seats,
    required this.selectedSeats,
    required this.user,
    required this.reservations,
    required this.paidReservation,
    required this.onSearch,
    required this.onSelectTrip,
    required this.onSeatChanged,
    required this.onAuth,
    required this.onPay,
    required this.onLoadReservations,
  });

  final TextEditingController origin;
  final TextEditingController destination;
  final List<dynamic> trips;
  final dynamic selectedTrip;
  final List<dynamic> seats;
  final Set<int> selectedSeats;
  final Map<String, dynamic>? user;
  final List<dynamic> reservations;
  final dynamic paidReservation;
  final VoidCallback onSearch;
  final ValueChanged<dynamic> onSelectTrip;
  final void Function(int id, bool selected) onSeatChanged;
  final VoidCallback onAuth;
  final VoidCallback onPay;
  final VoidCallback onLoadReservations;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _Section(icon: Icons.search, title: 'Busca tu ruta'),
        LayoutBuilder(
          builder: (context, constraints) {
            final compact = constraints.maxWidth < 720;
            final fields = [
              _Field(
                controller: origin,
                label: 'Origen',
                icon: Icons.trip_origin,
              ),
              _Field(
                controller: destination,
                label: 'Destino',
                icon: Icons.flag,
              ),
            ];
            if (compact) return Column(children: fields);
            return Row(
              children: fields
                  .map(
                    (field) => Expanded(
                      child: Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: field,
                      ),
                    ),
                  )
                  .toList(),
            );
          },
        ),
        _Actions(
          children: [
            FilledButton.icon(
              onPressed: onSearch,
              icon: const Icon(Icons.search),
              label: const Text('Buscar'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        if (trips.isEmpty)
          const _EmptyState(
            text: 'No hay rutas creadas para ese origen y destino',
          )
        else
          ...trips.map(
            (trip) => _TripCard(
              trip: trip,
              selected: selectedTrip?['id'] == trip['id'],
              onSelect: () => onSelectTrip(trip),
            ),
          ),
        if (selectedTrip != null) ...[
          const SizedBox(height: 16),
          _Section(icon: Icons.event_seat, title: 'Selecciona asiento'),
          _SeatGrid(
            seats: seats,
            selectedSeats: selectedSeats,
            onSeatChanged: onSeatChanged,
          ),
          const SizedBox(height: 12),
          if (user == null)
            Card(
              child: ListTile(
                leading: const Icon(Icons.lock),
                title: const Text('Inicia sesion para reservar'),
                subtitle: const Text(
                  'Puedes crear cuenta como usuario o empresa.',
                ),
                trailing: FilledButton(
                  onPressed: onAuth,
                  child: const Text('Ingresar'),
                ),
              ),
            )
          else
            _PaymentCard(
              trip: selectedTrip,
              selectedSeatCount: selectedSeats.length,
              onPay: selectedSeats.isEmpty ? null : onPay,
            ),
        ],
        if (paidReservation != null) ...[
          const SizedBox(height: 16),
          _Section(icon: Icons.check_circle, title: 'Viaje confirmado'),
          _ReservationSummary(reservation: paidReservation),
        ],
        if (user != null && user?['role'] != 'company') ...[
          const SizedBox(height: 16),
          _Section(icon: Icons.receipt_long, title: 'Mis viajes'),
          _Actions(
            children: [
              OutlinedButton.icon(
                onPressed: onLoadReservations,
                icon: const Icon(Icons.refresh),
                label: const Text('Actualizar'),
              ),
            ],
          ),
          if (reservations.isEmpty)
            const _EmptyState(text: 'Aun no tienes reservas')
          else
            ...reservations.map(
              (reservation) => _ReservationSummary(reservation: reservation),
            ),
        ],
      ],
    );
  }
}

class _CompanyView extends StatelessWidget {
  const _CompanyView({
    required this.routes,
    required this.trips,
    required this.passengers,
    required this.scheduleId,
    required this.vehicleId,
    required this.driverId,
    required this.routeOrigin,
    required this.routeDestination,
    required this.routePrice,
    required this.routeDeparture,
    required this.routeArrival,
    required this.routeDistance,
    required this.routeMinutes,
    required this.onCreateRoute,
    required this.onCreateTrip,
    required this.onRefresh,
    required this.onUseSchedule,
  });

  final List<dynamic> routes;
  final List<dynamic> trips;
  final List<dynamic> passengers;
  final TextEditingController scheduleId;
  final TextEditingController vehicleId;
  final TextEditingController driverId;
  final TextEditingController routeOrigin;
  final TextEditingController routeDestination;
  final TextEditingController routePrice;
  final TextEditingController routeDeparture;
  final TextEditingController routeArrival;
  final TextEditingController routeDistance;
  final TextEditingController routeMinutes;
  final VoidCallback onCreateRoute;
  final VoidCallback onCreateTrip;
  final VoidCallback onRefresh;
  final ValueChanged<int> onUseSchedule;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _Section(icon: Icons.business, title: 'Modulo empresa'),
        _Actions(
          children: [
            OutlinedButton.icon(
              onPressed: onRefresh,
              icon: const Icon(Icons.sync),
              label: const Text('Actualizar'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        _Stats(
          routes: routes.length,
          trips: trips.length,
          passengers: passengers.length,
        ),
        const SizedBox(height: 16),
        _Section(icon: Icons.alt_route, title: 'Crear ruta'),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              children: [
                _Field(
                  controller: routeOrigin,
                  label: 'Origen',
                  icon: Icons.trip_origin,
                ),
                _Field(
                  controller: routeDestination,
                  label: 'Destino',
                  icon: Icons.flag,
                ),
                _Field(
                  controller: routePrice,
                  label: 'Precio',
                  icon: Icons.payments,
                ),
                _Field(
                  controller: routeDeparture,
                  label: 'Salida HH:mm',
                  icon: Icons.schedule,
                ),
                _Field(
                  controller: routeArrival,
                  label: 'Llegada HH:mm',
                  icon: Icons.schedule_send,
                ),
                _Field(
                  controller: routeDistance,
                  label: 'Distancia km',
                  icon: Icons.straighten,
                ),
                _Field(
                  controller: routeMinutes,
                  label: 'Duracion min',
                  icon: Icons.timelapse,
                ),
                _Actions(
                  children: [
                    FilledButton.icon(
                      onPressed: onCreateRoute,
                      icon: const Icon(Icons.add_location_alt),
                      label: const Text('Crear ruta'),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        _Section(icon: Icons.route, title: 'Rutas y horarios'),
        if (routes.isEmpty)
          const _EmptyState(text: 'No hay rutas creadas')
        else
          ...routes.map(
            (route) =>
                _CompanyRouteCard(route: route, onUseSchedule: onUseSchedule),
          ),
        const SizedBox(height: 16),
        _Section(icon: Icons.add_road, title: 'Crear viaje'),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              children: [
                _Field(
                  controller: scheduleId,
                  label: 'Horario ID',
                  icon: Icons.tag,
                ),
                _Field(
                  controller: vehicleId,
                  label: 'Vehiculo ID',
                  icon: Icons.directions_bus,
                ),
                _Field(
                  controller: driverId,
                  label: 'Conductor ID',
                  icon: Icons.badge,
                ),
                _Actions(
                  children: [
                    FilledButton.icon(
                      onPressed: onCreateTrip,
                      icon: const Icon(Icons.add),
                      label: const Text('Crear viaje para manana'),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        _Section(icon: Icons.people, title: 'Pasajeros'),
        if (passengers.isEmpty)
          const _EmptyState(text: 'Sin pasajeros registrados')
        else
          ...passengers.map(
            (passenger) => _PassengerCard(passenger: passenger),
          ),
      ],
    );
  }
}

class _TripCard extends StatelessWidget {
  const _TripCard({
    required this.trip,
    required this.selected,
    required this.onSelect,
  });

  final dynamic trip;
  final bool selected;
  final VoidCallback onSelect;

  @override
  Widget build(BuildContext context) {
    final route = trip['route'] is Map ? trip['route'] as Map : {};
    final capacity = math.max((trip['max_passengers'] as int?) ?? 1, 1);
    final current = (trip['current_passengers'] as int?) ?? 0;
    final price = trip['price'] ?? route['base_price'] ?? 0;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Card(
        color: selected ? const Color(0xffecfdf5) : Colors.white,
        child: ListTile(
          leading: const Icon(Icons.directions_bus),
          title: Text(
            '${route['origin'] ?? '-'} -> ${route['destination'] ?? '-'}',
          ),
          subtitle: Text(
            '${trip['departure_datetime'] ?? trip['departure_date'] ?? ''}\n'
            'Cupos ${capacity - current} de $capacity - COP $price',
          ),
          isThreeLine: true,
          trailing: FilledButton(
            onPressed: onSelect,
            child: Text(selected ? 'Elegida' : 'Elegir'),
          ),
        ),
      ),
    );
  }
}

class _SeatGrid extends StatelessWidget {
  const _SeatGrid({
    required this.seats,
    required this.selectedSeats,
    required this.onSeatChanged,
  });

  final List<dynamic> seats;
  final Set<int> selectedSeats;
  final void Function(int id, bool selected) onSeatChanged;

  @override
  Widget build(BuildContext context) {
    if (seats.isEmpty) {
      return const _EmptyState(text: 'Cargando asientos del viaje');
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Wrap(
          spacing: 8,
          runSpacing: 8,
          children: seats.map((seat) {
            final id = seat['id'] as int;
            final available = seat['is_available'] == true;
            final selected = selectedSeats.contains(id);
            return FilterChip(
              selected: selected,
              onSelected: available
                  ? (value) => onSeatChanged(id, value)
                  : null,
              avatar: Icon(
                Icons.event_seat,
                size: 18,
                color: available ? null : Colors.red.shade500,
              ),
              label: Text(seat['seat_number'].toString()),
            );
          }).toList(),
        ),
      ),
    );
  }
}

class _PaymentCard extends StatelessWidget {
  const _PaymentCard({
    required this.trip,
    required this.selectedSeatCount,
    required this.onPay,
  });

  final dynamic trip;
  final int selectedSeatCount;
  final VoidCallback? onPay;

  @override
  Widget build(BuildContext context) {
    final route = trip['route'] is Map ? trip['route'] as Map : {};
    final price = ((trip['price'] ?? route['base_price'] ?? 0) as num)
        .toDouble();
    final total = price * selectedSeatCount;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          children: [
            const Icon(Icons.payments),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                selectedSeatCount == 0
                    ? 'Selecciona asiento para pagar'
                    : '$selectedSeatCount asiento(s) - Total COP ${total.toStringAsFixed(0)}',
              ),
            ),
            FilledButton.icon(
              onPressed: onPay,
              icon: const Icon(Icons.credit_card),
              label: const Text('Pagar'),
            ),
          ],
        ),
      ),
    );
  }
}

class _ReservationSummary extends StatelessWidget {
  const _ReservationSummary({required this.reservation});

  final dynamic reservation;

  @override
  Widget build(BuildContext context) {
    final trip = reservation['trip'] is Map ? reservation['trip'] as Map : {};
    final route = trip['route'] is Map ? trip['route'] as Map : {};
    final seats = reservation['seats'] is List
        ? (reservation['seats'] as List)
              .map((seat) => seat['seat_number'] ?? '-')
              .join(', ')
        : '-';

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Card(
        child: ListTile(
          leading: Icon(
            reservation['status'] == 'paid'
                ? Icons.check_circle
                : Icons.receipt,
          ),
          title: Text(
            '${reservation['code'] ?? 'Reserva'} - ${reservation['status']}',
          ),
          subtitle: Text(
            '${route['origin'] ?? ''} -> ${route['destination'] ?? ''}\n'
            'Asientos: $seats - Total COP ${reservation['total_amount'] ?? ''}',
          ),
          isThreeLine: true,
        ),
      ),
    );
  }
}

class _CompanyRouteCard extends StatelessWidget {
  const _CompanyRouteCard({required this.route, required this.onUseSchedule});

  final dynamic route;
  final ValueChanged<int> onUseSchedule;

  @override
  Widget build(BuildContext context) {
    final schedules = route['schedules'] is List
        ? route['schedules'] as List
        : [];
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '${route['origin']} -> ${route['destination']}',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _Pill(
                    icon: Icons.tag,
                    text: route['code']?.toString() ?? '-',
                  ),
                  _Pill(
                    icon: Icons.payments,
                    text: 'COP ${route['base_price']}',
                  ),
                  _Pill(
                    icon: Icons.straighten,
                    text: '${route['distance_km'] ?? '-'} km',
                  ),
                ],
              ),
              const SizedBox(height: 8),
              ...schedules.map(
                (schedule) => ListTile(
                  dense: true,
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.schedule),
                  title: Text('Horario #${schedule['id']}'),
                  subtitle: Text(
                    '${schedule['departure_time']} - ${schedule['estimated_arrival_time']}',
                  ),
                  trailing: TextButton(
                    onPressed: () => onUseSchedule(schedule['id'] as int),
                    child: const Text('Usar'),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _PassengerCard extends StatelessWidget {
  const _PassengerCard({required this.passenger});

  final dynamic passenger;

  @override
  Widget build(BuildContext context) {
    final person = passenger['passenger'] is Map
        ? passenger['passenger'] as Map
        : {};
    final trip = passenger['trip'] is Map ? passenger['trip'] as Map : {};
    final route = trip['route'] is Map ? trip['route'] as Map : {};
    final seats = passenger['seats'] is List
        ? (passenger['seats'] as List)
              .map((seat) => seat['seat_number'])
              .join(', ')
        : '-';

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Card(
        child: ListTile(
          leading: const Icon(Icons.person),
          title: Text('${person['name'] ?? '-'} - ${passenger['status']}'),
          subtitle: Text(
            '${route['origin'] ?? ''} -> ${route['destination'] ?? ''}\n'
            'Asientos: $seats - ${person['phone'] ?? person['email'] ?? ''}',
          ),
          isThreeLine: true,
        ),
      ),
    );
  }
}

class _Stats extends StatelessWidget {
  const _Stats({
    required this.routes,
    required this.trips,
    required this.passengers,
  });

  final int routes;
  final int trips;
  final int passengers;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final columns = constraints.maxWidth < 680 ? 1 : 3;
        final items = [
          _Stat('Rutas', routes.toString(), Icons.alt_route),
          _Stat('Viajes', trips.toString(), Icons.directions_bus),
          _Stat('Pasajeros', passengers.toString(), Icons.people),
        ];
        return GridView.count(
          crossAxisCount: columns,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisSpacing: 10,
          mainAxisSpacing: 10,
          childAspectRatio: columns == 1 ? 5 : 2.6,
          children: items
              .map(
                (item) => Card(
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Row(
                      children: [
                        Icon(item.icon),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(item.label),
                              Text(
                                item.value,
                                style: Theme.of(context).textTheme.titleLarge,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              )
              .toList(),
        );
      },
    );
  }
}

class _Stat {
  _Stat(this.label, this.value, this.icon);

  final String label;
  final String value;
  final IconData icon;
}

class _Section extends StatelessWidget {
  const _Section({required this.icon, required this.title});

  final IconData icon;
  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Icon(icon, size: 20),
          const SizedBox(width: 8),
          Text(title, style: Theme.of(context).textTheme.titleMedium),
        ],
      ),
    );
  }
}

class _Field extends StatelessWidget {
  const _Field({
    required this.controller,
    required this.label,
    required this.icon,
    this.obscureText = false,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final bool obscureText;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: TextField(
        controller: controller,
        obscureText: obscureText,
        decoration: InputDecoration(labelText: label, prefixIcon: Icon(icon)),
      ),
    );
  }
}

class _Actions extends StatelessWidget {
  const _Actions({required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Wrap(spacing: 8, runSpacing: 8, children: children);
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xffdbe3ef)),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16),
          const SizedBox(width: 6),
          Flexible(child: Text(text, overflow: TextOverflow.ellipsis)),
        ],
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Center(child: Text(text)),
      ),
    );
  }
}
