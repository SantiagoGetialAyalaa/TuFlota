<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Repositories\DriverRepositoryInterface;
use App\Infrastructure\Persistence\Models\DebtModel;
use App\Infrastructure\Persistence\Models\DriverDocumentModel;
use App\Infrastructure\Persistence\Models\DriverModel;
use App\Infrastructure\Persistence\Models\VehicleModel;

class DriverRepository implements DriverRepositoryInterface
{
    public function exists(int $driverId): bool
    {
        return DriverModel::query()->whereKey($driverId)->exists();
    }

    public function isEligibleForQueue(int $driverId): bool
    {
        $driver = DriverModel::query()->find($driverId);

        if (! $driver || ! $driver->is_available || ! in_array($driver->status, ['approved', 'active'], true)) {
            return false;
        }

        if ($driver->license_expires_at && $driver->license_expires_at->isPast()) {
            return false;
        }

        $hasApprovedDocuments = DriverDocumentModel::query()
            ->where('driver_id', $driverId)
            ->where('status', 'approved')
            ->exists();

        $hasPendingDebt = DebtModel::query()
            ->where('driver_id', $driverId)
            ->where('status', 'pending')
            ->exists();

        $hasActiveVehicle = VehicleModel::query()
            ->where('driver_id', $driverId)
            ->where('status', 'active')
            ->exists();

        return $hasApprovedDocuments && ! $hasPendingDebt && $hasActiveVehicle;
    }

    public function getActiveVehicleId(int $driverId): ?int
    {
        $vehicleId = VehicleModel::query()
            ->where('driver_id', $driverId)
            ->where('status', 'active')
            ->orderBy('id')
            ->value('id');

        return $vehicleId ? (int) $vehicleId : null;
    }
}
