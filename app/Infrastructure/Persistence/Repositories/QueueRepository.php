<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Exceptions\BusinessRuleViolationException;
use App\Domain\Repositories\QueueRepositoryInterface;
use App\Infrastructure\Persistence\Models\QueueModel;

class QueueRepository implements QueueRepositoryInterface
{
    public function enqueue(int $driverId, int $vehicleId): array
    {
        $alreadyQueued = QueueModel::query()
            ->where('driver_id', $driverId)
            ->where('status', 'waiting')
            ->exists();

        if ($alreadyQueued) {
            throw new BusinessRuleViolationException('El conductor ya se encuentra en la cola FIFO.');
        }

        $position = QueueModel::query()
            ->where('status', 'waiting')
            ->count() + 1;

        $entry = QueueModel::query()->create([
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'status' => 'waiting',
            'joined_at' => now(),
        ]);

        return [
            'id' => (int) $entry->id,
            'driver_id' => (int) $entry->driver_id,
            'vehicle_id' => (int) $entry->vehicle_id,
            'status' => (string) $entry->status,
            'position' => $position,
            'joined_at' => $entry->joined_at?->toDateTimeString(),
        ];
    }
}
