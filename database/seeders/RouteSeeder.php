<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\DriverDocumentModel;
use App\Infrastructure\Persistence\Models\DriverModel;
use App\Infrastructure\Persistence\Models\VehicleModel;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $driverUser = User::query()->firstOrCreate(
            ['email' => 'driver@example.com'],
            [
                'name' => 'Demo Driver',
                'phone' => '3000000000',
                'status' => 'active',
                'role' => 'user',
                'password' => bcrypt('password'),
            ],
        );

        $driver = DriverModel::query()->firstOrCreate([
            'license_number' => 'LIC-FLOTA-001',
        ], [
            'user_id' => $driverUser->id,
            'license_expires_at' => now()->addYear()->toDateString(),
            'status' => 'approved',
            'is_available' => true,
            'approved_at' => now(),
        ]);

        DriverDocumentModel::query()->firstOrCreate([
            'driver_id' => $driver->id,
            'type' => 'license',
        ], [
            'number' => 'LIC-FLOTA-001',
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $vehicle = VehicleModel::query()->firstOrCreate([
            'plate' => 'FLX-100',
        ], [
            'driver_id' => $driver->id,
            'brand' => 'Volvo',
            'model' => '9700',
            'type' => 'bus',
            'capacity' => 40,
            'status' => 'active',
        ]);

        $route = Route::query()->updateOrCreate([
            'code' => 'PST-TNG',
        ], [
            'origin' => 'Pasto',
            'origin_latitude' => 1.2136,
            'origin_longitude' => -77.2811,
            'destination' => 'Tangua',
            'destination_latitude' => 1.0917,
            'destination_longitude' => -77.3942,
            'distance_km' => 32.5,
            'estimated_duration_minutes' => 60,
            'base_price' => 8000,
            'is_active' => true,
        ]);

        $schedule = Schedule::query()->firstOrCreate([
            'route_id' => $route->id,
            'departure_time' => '08:00:00',
        ], [
            'estimated_arrival_time' => '09:00:00',
            'price' => 8000,
            'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true,
        ]);

        Trip::query()->firstOrCreate([
            'schedule_id' => $schedule->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'departure_datetime' => now()->addDay()->setTime(8, 0),
        ], [
            'status' => 'scheduled',
            'current_passengers' => 0,
            'max_passengers' => $vehicle->capacity,
            'departure_date' => now()->addDay()->toDateString(),
            'estimated_arrival_datetime' => now()->addDay()->setTime(9, 0),
        ]);
    }
}
