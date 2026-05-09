import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

void main() {
  runApp(const FlotaApp());
}

class FlotaApp extends StatelessWidget {
  const FlotaApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Flota',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xff2563eb),
          brightness: Brightness.light,
        ),
        scaffoldBackgroundColor: const Color(0xfff6f8fb),
        inputDecorationTheme: const InputDecorationTheme(
          border: OutlineInputBorder(),
          isDense: true,
        ),
        cardTheme: const CardThemeData(
          margin: EdgeInsets.zero,
          elevation: 0,
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

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    }

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
  final baseUrl = TextEditingController(text: 'http://127.0.0.1:8001/api');
  final email = TextEditingController(text: 'test@example.com');
  final password = TextEditingController(text: 'password');
  final name = TextEditingController(text: 'Test User');
  final phone = TextEditingController(text: '3001234567');
  final origin = TextEditingController();
  final destination = TextEditingController();
  final date = TextEditingController();
  final tripId = TextEditingController();
  final userId = TextEditingController(text: '1');
  final reservationId = TextEditingController();
  final scheduleId = TextEditingController(text: '1');
  final vehicleId = TextEditingController(text: '1');
  final driverId = TextEditingController(text: '1');
  final queueDriverId = TextEditingController(text: '1');

  bool busy = false;
  String? error;
  String? notice;
  Map<String, dynamic>? currentUser;
  List<dynamic> trips = [];
  List<dynamic> seats = [];
  List<dynamic> reservations = [];
  final selectedSeats = <int>{};

  @override
  void initState() {
    super.initState();
    api = ApiClient(baseUrl.text);
    loadTrips();
  }

  @override
  void dispose() {
    baseUrl.dispose();
    email.dispose();
    password.dispose();
    name.dispose();
    phone.dispose();
    origin.dispose();
    destination.dispose();
    date.dispose();
    tripId.dispose();
    userId.dispose();
    reservationId.dispose();
    scheduleId.dispose();
    vehicleId.dispose();
    driverId.dispose();
    queueDriverId.dispose();
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

  Future<void> login() {
    return runTask(() async {
      final data = await api.post('/auth/login', {
        'email': email.text,
        'password': password.text,
      });
      api.token = data['token']?.toString();
      currentUser = Map<String, dynamic>.from(data['user'] as Map);
      userId.text = currentUser!['id'].toString();
      notice = 'Sesion iniciada: ${currentUser!['name']}';
      await loadTrips(quiet: true);
      await loadReservations(quiet: true);
      setState(() {});
    });
  }

  Future<void> register() {
    return runTask(() async {
      final data = await api.post('/auth/register', {
        'name': name.text,
        'email': email.text,
        'phone': phone.text,
        'password': password.text,
      });
      currentUser = Map<String, dynamic>.from(data as Map);
      userId.text = currentUser!['id'].toString();
      notice = 'Usuario creado: ${currentUser!['name']}';
      setState(() {});
    });
  }

  Future<void> loadTrips({bool quiet = false}) {
    return runTask(() async {
      final data = await api.get('/trips', {
        'origin': origin.text,
        'destination': destination.text,
        'date': date.text,
      });
      trips = data as List<dynamic>;
      if (trips.isNotEmpty && tripId.text.trim().isEmpty) {
        tripId.text = trips.first['id'].toString();
      }
      if (!quiet) notice = '${trips.length} viaje(s) cargado(s)';
      setState(() {});
    });
  }

  Future<void> createTrip() {
    return runTask(() async {
      final data = await api.post('/trips', {
        'schedule_id': int.parse(scheduleId.text),
        'vehicle_id': int.parse(vehicleId.text),
        'driver_id': int.parse(driverId.text),
        'departure_datetime': DateTime.now()
            .add(const Duration(days: 1))
            .toIso8601String(),
        'status': 'scheduled',
      });
      notice = 'Viaje creado #${data['id']}';
      await loadTrips(quiet: true);
    });
  }

  Future<void> loadSeats() {
    return runTask(() async {
      final id = int.parse(tripId.text);
      final data = await api.get('/seats/trips/$id');
      seats = data as List<dynamic>;
      selectedSeats.clear();
      notice = '${seats.length} asiento(s) cargado(s)';
      setState(() {});
    });
  }

  Future<void> createReservation() {
    return runTask(() async {
      final data = await api.post('/reservations', {
        'user_id': int.parse(userId.text),
        'trip_id': int.parse(tripId.text),
        'seat_ids': selectedSeats.toList(),
        'reserved_minutes': 15,
      });
      reservationId.text = data['id'].toString();
      notice = 'Reserva creada: ${data['code']}';
      await loadSeats();
      await loadReservations(quiet: true);
    });
  }

  Future<void> assignSeats() {
    return runTask(() async {
      final data = await api.post('/seats/assign', {
        'reservation_id': int.parse(reservationId.text),
        'seat_ids': selectedSeats.toList(),
      });
      notice = 'Asientos asignados a reserva ${data['code']}';
      await loadSeats();
      await loadReservations(quiet: true);
    });
  }

  Future<void> loadReservations({bool quiet = false}) {
    return runTask(() async {
      final id = int.parse(userId.text);
      final data = await api.get('/users/$id/reservations');
      reservations = data as List<dynamic>;
      if (!quiet) notice = '${reservations.length} reserva(s) cargada(s)';
      setState(() {});
    });
  }

  Future<void> cancelReservation(int id) {
    return runTask(() async {
      await api.delete('/reservations/$id');
      notice = 'Reserva #$id cancelada';
      await loadReservations(quiet: true);
      if (tripId.text.trim().isNotEmpty) await loadSeats();
    });
  }

  Future<void> joinQueue() {
    return runTask(() async {
      final data = await api.post('/drivers/queue', {
        'driver_id': int.parse(queueDriverId.text),
      });
      notice = 'Conductor en cola: #${data['driver_id'] ?? queueDriverId.text}';
    });
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 5,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Flota'),
          bottom: const TabBar(
            isScrollable: true,
            tabs: [
              Tab(icon: Icon(Icons.person), text: 'Auth'),
              Tab(icon: Icon(Icons.route), text: 'Viajes'),
              Tab(icon: Icon(Icons.event_seat), text: 'Reservar'),
              Tab(icon: Icon(Icons.receipt_long), text: 'Mis reservas'),
              Tab(icon: Icon(Icons.local_shipping), text: 'Operacion'),
            ],
          ),
        ),
        body: SafeArea(
          child: Column(
            children: [
              _ConnectionBar(
                controller: baseUrl,
                busy: busy,
                user: currentUser,
                error: error,
                notice: notice,
              ),
              Expanded(
                child: TabBarView(
                  children: [
                    _AuthTab(
                      email: email,
                      password: password,
                      name: name,
                      phone: phone,
                      onLogin: login,
                      onRegister: register,
                    ),
                    _TripsTab(
                      origin: origin,
                      destination: destination,
                      date: date,
                      tripId: tripId,
                      trips: trips,
                      onSearch: loadTrips,
                      onUseTrip: (id) {
                        tripId.text = id.toString();
                        loadSeats();
                      },
                    ),
                    _ReservationTab(
                      tripId: tripId,
                      userId: userId,
                      reservationId: reservationId,
                      seats: seats,
                      selectedSeats: selectedSeats,
                      onLoadSeats: loadSeats,
                      onReserve: createReservation,
                      onAssign: assignSeats,
                      onSeatChanged: (id, selected) {
                        setState(() {
                          if (selected) {
                            selectedSeats.add(id);
                          } else {
                            selectedSeats.remove(id);
                          }
                        });
                      },
                    ),
                    _ReservationsTab(
                      userId: userId,
                      reservations: reservations,
                      onLoad: loadReservations,
                      onCancel: cancelReservation,
                    ),
                    _OperationsTab(
                      scheduleId: scheduleId,
                      vehicleId: vehicleId,
                      driverId: driverId,
                      queueDriverId: queueDriverId,
                      onCreateTrip: createTrip,
                      onJoinQueue: joinQueue,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ConnectionBar extends StatelessWidget {
  const _ConnectionBar({
    required this.controller,
    required this.busy,
    required this.user,
    required this.error,
    required this.notice,
  });

  final TextEditingController controller;
  final bool busy;
  final Map<String, dynamic>? user;
  final String? error;
  final String? notice;

  @override
  Widget build(BuildContext context) {
    final color = error == null ? const Color(0xff0f766e) : Colors.red.shade700;
    final text = error ?? notice;

    return Material(
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: controller,
                    decoration: const InputDecoration(
                      labelText: 'Base URL API',
                      prefixIcon: Icon(Icons.link),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                if (busy)
                  const SizedBox(
                    width: 28,
                    height: 28,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(user == null ? Icons.lock_open : Icons.verified_user),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    user == null
                        ? 'Sin sesion activa'
                        : 'Usuario #${user!['id']} - ${user!['name']}',
                  ),
                ),
              ],
            ),
            if (text != null) ...[
              const SizedBox(height: 8),
              Align(
                alignment: Alignment.centerLeft,
                child: Text(text, style: TextStyle(color: color)),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _AuthTab extends StatelessWidget {
  const _AuthTab({
    required this.email,
    required this.password,
    required this.name,
    required this.phone,
    required this.onLogin,
    required this.onRegister,
  });

  final TextEditingController email;
  final TextEditingController password;
  final TextEditingController name;
  final TextEditingController phone;
  final VoidCallback onLogin;
  final VoidCallback onRegister;

  @override
  Widget build(BuildContext context) {
    return _Panel(
      children: [
        _Field(controller: name, label: 'Nombre', icon: Icons.badge),
        _Field(controller: email, label: 'Email', icon: Icons.email),
        _Field(controller: phone, label: 'Telefono', icon: Icons.phone),
        _Field(
          controller: password,
          label: 'Password',
          icon: Icons.key,
          obscureText: true,
        ),
        _Actions(
          children: [
            FilledButton.icon(
              onPressed: onLogin,
              icon: const Icon(Icons.login),
              label: const Text('Login'),
            ),
            OutlinedButton.icon(
              onPressed: onRegister,
              icon: const Icon(Icons.person_add),
              label: const Text('Registrar'),
            ),
          ],
        ),
      ],
    );
  }
}

class _TripsTab extends StatelessWidget {
  const _TripsTab({
    required this.origin,
    required this.destination,
    required this.date,
    required this.tripId,
    required this.trips,
    required this.onSearch,
    required this.onUseTrip,
  });

  final TextEditingController origin;
  final TextEditingController destination;
  final TextEditingController date;
  final TextEditingController tripId;
  final List<dynamic> trips;
  final VoidCallback onSearch;
  final ValueChanged<int> onUseTrip;

  @override
  Widget build(BuildContext context) {
    return _Panel(
      children: [
        LayoutBuilder(
          builder: (context, constraints) {
            final compact = constraints.maxWidth < 680;
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
              _Field(
                controller: date,
                label: 'Fecha YYYY-MM-DD',
                icon: Icons.calendar_today,
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
              label: const Text('Buscar viajes'),
            ),
          ],
        ),
        _Field(
          controller: tripId,
          label: 'Viaje seleccionado',
          icon: Icons.tag,
        ),
        ...trips.map((trip) => _TripCard(trip: trip, onUseTrip: onUseTrip)),
      ],
    );
  }
}

class _ReservationTab extends StatelessWidget {
  const _ReservationTab({
    required this.tripId,
    required this.userId,
    required this.reservationId,
    required this.seats,
    required this.selectedSeats,
    required this.onLoadSeats,
    required this.onReserve,
    required this.onAssign,
    required this.onSeatChanged,
  });

  final TextEditingController tripId;
  final TextEditingController userId;
  final TextEditingController reservationId;
  final List<dynamic> seats;
  final Set<int> selectedSeats;
  final VoidCallback onLoadSeats;
  final VoidCallback onReserve;
  final VoidCallback onAssign;
  final void Function(int id, bool selected) onSeatChanged;

  @override
  Widget build(BuildContext context) {
    final canSubmit = selectedSeats.isNotEmpty;

    return _Panel(
      children: [
        _Field(controller: userId, label: 'Usuario ID', icon: Icons.person),
        _Field(controller: tripId, label: 'Viaje ID', icon: Icons.route),
        _Field(
          controller: reservationId,
          label: 'Reserva ID para asignar',
          icon: Icons.confirmation_number,
        ),
        _Actions(
          children: [
            FilledButton.icon(
              onPressed: onLoadSeats,
              icon: const Icon(Icons.event_seat),
              label: const Text('Cargar asientos'),
            ),
            OutlinedButton.icon(
              onPressed: canSubmit ? onReserve : null,
              icon: const Icon(Icons.add_card),
              label: const Text('Crear reserva'),
            ),
            OutlinedButton.icon(
              onPressed: canSubmit ? onAssign : null,
              icon: const Icon(Icons.playlist_add_check),
              label: const Text('Asignar'),
            ),
          ],
        ),
        Wrap(
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
                color: available ? null : Colors.red.shade400,
              ),
              label: Text(seat['seat_number'].toString()),
            );
          }).toList(),
        ),
      ],
    );
  }
}

class _ReservationsTab extends StatelessWidget {
  const _ReservationsTab({
    required this.userId,
    required this.reservations,
    required this.onLoad,
    required this.onCancel,
  });

  final TextEditingController userId;
  final List<dynamic> reservations;
  final VoidCallback onLoad;
  final ValueChanged<int> onCancel;

  @override
  Widget build(BuildContext context) {
    return _Panel(
      children: [
        _Field(controller: userId, label: 'Usuario ID', icon: Icons.person),
        _Actions(
          children: [
            FilledButton.icon(
              onPressed: onLoad,
              icon: const Icon(Icons.refresh),
              label: const Text('Cargar reservas'),
            ),
          ],
        ),
        ...reservations.map(
          (reservation) =>
              _ReservationCard(reservation: reservation, onCancel: onCancel),
        ),
      ],
    );
  }
}

class _OperationsTab extends StatelessWidget {
  const _OperationsTab({
    required this.scheduleId,
    required this.vehicleId,
    required this.driverId,
    required this.queueDriverId,
    required this.onCreateTrip,
    required this.onJoinQueue,
  });

  final TextEditingController scheduleId;
  final TextEditingController vehicleId;
  final TextEditingController driverId;
  final TextEditingController queueDriverId;
  final VoidCallback onCreateTrip;
  final VoidCallback onJoinQueue;

  @override
  Widget build(BuildContext context) {
    return _Panel(
      children: [
        _Field(controller: scheduleId, label: 'Schedule ID', icon: Icons.timer),
        _Field(
          controller: vehicleId,
          label: 'Vehicle ID',
          icon: Icons.directions_bus,
        ),
        _Field(controller: driverId, label: 'Driver ID', icon: Icons.badge),
        _Actions(
          children: [
            FilledButton.icon(
              onPressed: onCreateTrip,
              icon: const Icon(Icons.add_road),
              label: const Text('Crear viaje'),
            ),
          ],
        ),
        const Divider(height: 32),
        _Field(
          controller: queueDriverId,
          label: 'Driver ID para cola',
          icon: Icons.queue,
        ),
        _Actions(
          children: [
            OutlinedButton.icon(
              onPressed: onJoinQueue,
              icon: const Icon(Icons.queue_play_next),
              label: const Text('Entrar a cola'),
            ),
          ],
        ),
      ],
    );
  }
}

class _Panel extends StatelessWidget {
  const _Panel({required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 980),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: children
                .map(
                  (child) => Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: child,
                  ),
                )
                .toList(),
          ),
        ),
      ],
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
    return TextField(
      controller: controller,
      obscureText: obscureText,
      decoration: InputDecoration(labelText: label, prefixIcon: Icon(icon)),
    );
  }
}

class _TripCard extends StatelessWidget {
  const _TripCard({required this.trip, required this.onUseTrip});

  final dynamic trip;
  final ValueChanged<int> onUseTrip;

  @override
  Widget build(BuildContext context) {
    final route = trip['route'] is Map ? trip['route'] as Map : {};
    final vehicle = trip['vehicle'] is Map ? trip['vehicle'] as Map : {};
    final driver = trip['driver'] is Map ? trip['driver'] as Map : {};
    final id = trip['id'] as int;

    return Card(
      child: ListTile(
        title: Text(
          '${route['origin'] ?? 'Origen'} -> ${route['destination'] ?? 'Destino'}',
        ),
        subtitle: Text(
          'Viaje #$id  ${trip['departure_datetime'] ?? trip['departure_date'] ?? ''}\n'
          'Estado: ${trip['status']}  Cupos: ${trip['current_passengers']}/${trip['max_passengers']}\n'
          'Vehiculo: ${vehicle['plate'] ?? '-'}  Conductor: ${driver['name'] ?? '-'}',
        ),
        isThreeLine: true,
        trailing: IconButton(
          tooltip: 'Usar viaje',
          onPressed: () => onUseTrip(id),
          icon: const Icon(Icons.arrow_forward),
        ),
      ),
    );
  }
}

class _ReservationCard extends StatelessWidget {
  const _ReservationCard({required this.reservation, required this.onCancel});

  final dynamic reservation;
  final ValueChanged<int> onCancel;

  @override
  Widget build(BuildContext context) {
    final id = reservation['id'] as int;
    final seats = reservation['seats'] is List
        ? (reservation['seats'] as List)
              .map((seat) => seat['seat_number'] ?? seat['seat_id'])
              .join(', ')
        : '-';
    final trip = reservation['trip'] is Map ? reservation['trip'] as Map : {};
    final route = trip['route'] is Map ? trip['route'] as Map : {};

    return Card(
      child: ListTile(
        title: Text('${reservation['code']} - ${reservation['status']}'),
        subtitle: Text(
          'Total: ${reservation['total_amount']}  Asientos: $seats\n'
          '${route['origin'] ?? ''} -> ${route['destination'] ?? ''}  ${trip['departure_datetime'] ?? ''}',
        ),
        isThreeLine: true,
        trailing: IconButton(
          tooltip: 'Cancelar reserva',
          onPressed: reservation['status'] == 'cancelled'
              ? null
              : () => onCancel(id),
          icon: const Icon(Icons.cancel_outlined),
        ),
      ),
    );
  }
}
