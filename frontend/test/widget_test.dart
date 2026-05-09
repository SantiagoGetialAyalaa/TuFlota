import 'package:flutter_test/flutter_test.dart';

import 'package:transport_frontend/main.dart';

void main() {
  testWidgets('Flota app renders the module tabs', (WidgetTester tester) async {
    await tester.pumpWidget(const FlotaApp());

    expect(find.text('Flota'), findsOneWidget);
    expect(find.text('Auth'), findsOneWidget);
    expect(find.text('Viajes'), findsOneWidget);
    expect(find.text('Reservar'), findsOneWidget);
  });

  testWidgets('Base URL is editable', (WidgetTester tester) async {
    await tester.pumpWidget(const FlotaApp());

    expect(find.text('http://127.0.0.1:8001/api'), findsOneWidget);
    await tester.enterText(
      find.bySemanticsLabel('Base URL API'),
      'http://localhost:8000/api',
    );
    await tester.pump();

    expect(find.text('http://localhost:8000/api'), findsOneWidget);
  });
}
