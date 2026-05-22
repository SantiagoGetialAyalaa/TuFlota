<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\Trip;
use App\Domain\Repositories\TripRepositoryInterface;
use App\Infrastructure\Persistence\Models\DriverModel;
use App\Infrastructure\Persistence\Models\ScheduleModel;
use App\Infrastructure\Persistence\Models\TripModel;
use App\Infrastructure\Persistence\Models\VehicleModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TripRepository implements TripRepositoryInterface
{
    private array $reservableStatuses = ['scheduled', 'boarding', 'active'];

    public function search(array $filters = []): array
    {
        $query = TripModel::query()->with(['schedule.route', 'vehicle', 'driver.user']);

        if (! empty($filters['origin'])) {
            $query->whereHas('schedule.route', function (Builder $builder) use ($filters) {
                $builder->where('origin', 'like', '%'.$filters['origin'].'%');
            });
        }

        if (! empty($filters['destination'])) {
            $query->whereHas('schedule.route', function (Builder $builder) use ($filters) {
                $builder->where('destination', 'like', '%'.$filters['destination'].'%');
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date'])) {
            $query->where(function (Builder $builder) use ($filters) {
                $builder
                    ->whereDate('departure_datetime', $filters['date'])
                    ->orWhereDate('departure_date', $filters['date']);
            });
        }

        return $query
            ->orderBy('departure_datetime')
            ->orderBy('departure_date')
            ->get()
            ->map(fn (TripModel $trip) => $this->mapTrip($trip))
            ->all();
    }

    public function create(array $attributes): Trip
    {
        $trip = TripModel::query()->create($attributes);
        $trip->load(['schedule.route', 'vehicle', 'driver.user']);

        return $this->mapTrip($trip);
    }

    public function findById(int $id): ?Trip
    {
        $trip = TripModel::query()
            ->with(['schedule.route', 'vehicle', 'driver.user'])
            ->find($id);

        return $trip ? $this->mapTrip($trip) : null;
    }

    public function findActiveById(int $id): ?Trip
    {
        $trip = TripModel::query()
            ->with(['schedule.route', 'vehicle', 'driver.user'])
            ->whereKey($id)
            ->whereIn('status', $this->reservableStatuses)
            ->first();

        return $trip ? $this->mapTrip($trip) : null;
    }

    public function scheduleExists(int $scheduleId): bool
    {
        return ScheduleModel::query()
            ->whereKey($scheduleId)
            ->where('is_active', true)
            ->exists();
    }

    public function vehicleExists(int $vehicleId): bool
    {
        return VehicleModel::query()->whereKey($vehicleId)->exists();
    }

    public function driverExists(int $driverId): bool
    {
        return DriverModel::query()->whereKey($driverId)->exists();
    }

    public function vehicleIsActive(int $vehicleId): bool
    {
        return VehicleModel::query()
            ->whereKey($vehicleId)
            ->where('status', 'active')
            ->exists();
    }

    public function getVehicleCapacity(int $vehicleId): int
    {
        return (int) VehicleModel::query()
            ->whereKey($vehicleId)
            ->value('capacity');
    }

    public function incrementPassengers(int $tripId, int $amount = 1): void
    {
        TripModel::query()
            ->whereKey($tripId)
            ->update([
                'current_passengers' => DB::raw('current_passengers + '.(int) $amount),
            ]);
    }

    public function decrementPassengers(int $tripId, int $amount = 1): void
    {
        $trip = TripModel::query()->find($tripId);

        if (! $trip) {
            return;
        }

        $trip->update([
            'current_passengers' => max(0, (int) $trip->current_passengers - $amount),
        ]);
    }

    private function mapTrip(TripModel $trip): Trip
    {
        $route = $trip->schedule?->route;

        return new Trip(
            id: (int) $trip->id,
            scheduleId: (int) $trip->schedule_id,
            vehicleId: (int) ($trip->vehicle_id ?? 0),
            driverId: (int) ($trip->driver_id ?? 0),
            status: (string) $trip->status,
            currentPassengers: (int) $trip->current_passengers,
            maxPassengers: (int) $trip->max_passengers,
            departureDate: $trip->departure_date?->toDateString(),
            departureDateTime: $trip->departure_datetime?->toDateTimeString(),
            estimatedArrivalDateTime: $trip->estimated_arrival_datetime?->toDateTimeString(),
            price: $trip->schedule ? (float) $trip->schedule->price : null,
            route: $route ? [
                'id' => (int) $route->id,
                'code' => (string) $route->code,
                'origin' => (string) $route->origin,
                'origin_latitude' => $route->origin_latitude !== null ? (float) $route->origin_latitude : null,
                'origin_longitude' => $route->origin_longitude !== null ? (float) $route->origin_longitude : null,
                'destination' => (string) $route->destination,
                'destination_latitude' => $route->destination_latitude !== null ? (float) $route->destination_latitude : null,
                'destination_longitude' => $route->destination_longitude !== null ? (float) $route->destination_longitude : null,
                'base_price' => (float) $route->base_price,
                'distance_km' => $route->distance_km !== null ? (float) $route->distance_km : null,
                'estimated_duration_minutes' => $route->estimated_duration_minutes !== null ? (int) $route->estimated_duration_minutes : null,
            ] : [],
            vehicle: $trip->vehicle ? [
                'id' => (int) $trip->vehicle->id,
                'plate' => (string) $trip->vehicle->plate,
                'model' => (string) $trip->vehicle->model,
                'type' => (string) $trip->vehicle->type,
                'capacity' => (int) $trip->vehicle->capacity,
                'status' => (string) $trip->vehicle->status,
            ] : [],
            driver: $trip->driver ? [
                'id' => (int) $trip->driver->id,
                'status' => (string) $trip->driver->status,
                'license_number' => (string) $trip->driver->license_number,
                'name' => $trip->driver->user?->name,
            ] : [],
        );
    }
}
