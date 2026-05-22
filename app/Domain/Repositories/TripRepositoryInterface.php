<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Trip;

interface TripRepositoryInterface
{
    public function search(array $filters = []): array;

    public function create(array $attributes): Trip;

    public function findById(int $id): ?Trip;

    public function findActiveById(int $id): ?Trip;

    public function scheduleExists(int $scheduleId): bool;

    public function vehicleExists(int $vehicleId): bool;

    public function driverExists(int $driverId): bool;

    public function vehicleIsActive(int $vehicleId): bool;

    public function getVehicleCapacity(int $vehicleId): int;

    public function incrementPassengers(int $tripId, int $amount = 1): void;

    public function decrementPassengers(int $tripId, int $amount = 1): void;
}
