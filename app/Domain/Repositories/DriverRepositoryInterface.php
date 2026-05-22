<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface DriverRepositoryInterface
{
    public function exists(int $driverId): bool;

    public function isEligibleForQueue(int $driverId): bool;

    public function getActiveVehicleId(int $driverId): ?int;
}
