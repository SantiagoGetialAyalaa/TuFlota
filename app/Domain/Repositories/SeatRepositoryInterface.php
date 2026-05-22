<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Seat;

interface SeatRepositoryInterface
{
    public function ensureVehicleSeats(int $vehicleId): array;

    public function getAvailableByTrip(int $tripId): array;

    public function findById(int $id): ?Seat;

    public function belongsToVehicle(int $seatId, int $vehicleId): bool;

    public function isAvailableForTrip(int $tripId, int $seatId): bool;
}
