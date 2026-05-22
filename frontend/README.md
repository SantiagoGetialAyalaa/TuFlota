# Flota Frontend

Frontend Flutter para la aplicacion Flota.

## Ejecutar

Desde `transport-app/frontend`:

```bash
flutter pub get
flutter run -d web-server --web-hostname 127.0.0.1 --web-port 8080
```

Si `8080` esta ocupado:

```bash
flutter run -d web-server --web-hostname 127.0.0.1 --web-port 8081
```

## Backend esperado

La app consume por defecto:

```text
http://127.0.0.1:8000/api
```

En Android emulator usa:

```text
http://10.0.2.2:8000/api
```

La URL tambien se puede cambiar desde el campo `API` dentro de la app.

## Verificacion

```bash
flutter analyze --no-pub
dart format lib/main.dart
```
