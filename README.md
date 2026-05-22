# Flota - Backend Laravel + Frontend Flutter

Aplicacion de transporte para buscar rutas, reservar asientos, pagar reservas y administrar operaciones desde una cuenta de empresa.

El proyecto esta dividido en:

- `transport-app/`: backend Laravel API.
- `transport-app/frontend/`: frontend Flutter multiplataforma.

## Funcionalidades

### Usuario pasajero

- Buscar viajes por origen y destino.
- Ver mensaje cuando no hay rutas creadas.
- Seleccionar una ruta disponible.
- Seleccionar asiento.
- Iniciar sesion o crear cuenta solo cuando va a reservar.
- Crear reserva.
- Pagar con pasarela simulada.
- Ver informacion del viaje confirmado y reservas propias.

### Empresa

- Crear cuenta como empresa.
- Crear rutas con origen, destino, precio, horarios, distancia y duracion.
- Crear viajes sobre horarios existentes.
- Ver rutas y horarios registrados.
- Ver pasajeros/reservas.
- Actualizacion automatica del modulo empresa desde el frontend.

### Backend

- API REST en Laravel.
- Arquitectura por capas: `Application`, `Domain`, `Infrastructure`, `Http`.
- Autenticacion JWT.
- Migraciones y seeders.
- Reservas, asientos, viajes, rutas, conductores, vehiculos, pagos y cola FIFO.
- Pago simulado para marcar reservas como pagadas.

## Requisitos

- PHP 8.3 o superior.
- Composer.
- Node.js y npm.
- Flutter SDK.
- SQLite o MySQL.

## Clonar el proyecto

```bash
git clone <URL_DEL_REPOSITORIO>
cd transport-app
```

## Configurar backend Laravel

Instala dependencias:

```bash
composer install
npm install
```

Crea el archivo de entorno:

```bash
cp .env.example .env
php artisan key:generate
```

### Opcion A: SQLite

Es la forma mas simple para desarrollo local.

En `.env` deja:

```env
DB_CONNECTION=sqlite
```

Crea la base:

```bash
touch database/database.sqlite
php artisan migrate --seed
```

### Opcion B: MySQL

Crea una base de datos, por ejemplo `flota`, y configura `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flota
DB_USERNAME=root
DB_PASSWORD=
```

Luego ejecuta:

```bash
php artisan migrate --seed
```

## Levantar backend

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

La API queda en:

```text
http://127.0.0.1:8000/api
```

## Configurar frontend Flutter

En otra terminal:

```bash
cd frontend
flutter pub get
```

Para web:

```bash
flutter run -d web-server --web-hostname 127.0.0.1 --web-port 8080
```

Abre:

```text
http://127.0.0.1:8080
```

Si el puerto esta ocupado, usa otro:

```bash
flutter run -d web-server --web-hostname 127.0.0.1 --web-port 8081
```

## URL de API en Flutter

El frontend usa por defecto:

- Web, macOS, iOS, Linux, Windows: `http://127.0.0.1:8000/api`
- Android emulator: `http://10.0.2.2:8000/api`

Tambien puedes cambiar la URL desde el campo `API` dentro de la app.

## Cuentas demo

Despues de `php artisan migrate --seed` quedan disponibles:

### Usuario

```text
Email: test@example.com
Password: password
```

### Empresa

```text
Email: empresa@example.com
Password: password
```

### Conductor demo

```text
Email: driver@example.com
Password: password
Licencia: LIC-FLOTA-001
```

## Flujo recomendado para probar

1. Levanta Laravel en `http://127.0.0.1:8000`.
2. Levanta Flutter web.
3. En la pantalla inicial busca:

```text
Origen: Pasto
Destino: Tangua
```

4. Selecciona la ruta disponible.
5. Selecciona un asiento.
6. Inicia sesion como usuario o crea una cuenta.
7. Paga la reserva.
8. Inicia sesion como empresa para ver pasajeros y administrar rutas/viajes.

## Endpoints principales

### Autenticacion

```http
POST /api/auth/register
POST /api/auth/login
```

`register` acepta:

```json
{
  "name": "Nombre",
  "email": "correo@example.com",
  "phone": "3001234567",
  "role": "user",
  "password": "password"
}
```

Roles permitidos:

- `user`
- `company`

### Viajes

```http
GET /api/trips
POST /api/trips
```

Filtros para buscar:

```text
origin
destination
date
status
```

### Asientos

```http
GET /api/seats/trips/{trip}
POST /api/seats/assign
```

### Reservas

```http
POST /api/reservations
POST /api/reservations/{reservation}/pay
DELETE /api/reservations/{reservation}
GET /api/users/{user}/reservations
```

### Empresa

```http
GET /api/company/routes
POST /api/company/routes
GET /api/company/passengers
```

### Conductores y cola FIFO

```http
POST /api/drivers/queue
```

## Estructura importante

```text
app/
  Application/UseCases/       Casos de uso
  Domain/                     Entidades, contratos y reglas de negocio
  Http/Controllers/           Controladores API
  Http/Requests/              Validaciones HTTP
  Infrastructure/             Persistencia y servicios concretos

database/
  migrations/                 Estructura de base de datos
  seeders/                    Datos demo

frontend/
  lib/main.dart               App Flutter
  pubspec.yaml                Dependencias Flutter

routes/
  api.php                     Rutas de la API
```

## Comandos utiles

Ejecutar tests Laravel:

```bash
php artisan test
```

Analizar Flutter:

```bash
cd frontend
flutter analyze --no-pub
```

Formatear Flutter:

```bash
dart format lib/main.dart
```

Formatear PHP con Pint:

```bash
./vendor/bin/pint
```

Recrear base desde cero en desarrollo:

```bash
php artisan migrate:fresh --seed
```

Limpiar caches Laravel:

```bash
php artisan optimize:clear
```

## Notas de desarrollo

- El pago actual es simulado y marca la reserva como `paid`.
- La app no usa Google Maps ni claves externas por ahora; muestra la informacion de ruta y coordenadas disponibles desde la API.
- Para crear viajes desde empresa se necesitan IDs validos de `schedule`, `vehicle` y `driver`. El seeder deja datos demo con IDs iniciales normalmente en `1`.
- El modulo empresa se refresca automaticamente desde Flutter y tambien puede actualizarse manualmente.

## Verificacion actual

El proyecto fue verificado con:

```bash
php artisan test
flutter analyze --no-pub
```
