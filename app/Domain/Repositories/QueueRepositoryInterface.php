<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface QueueRepositoryInterface
{
    public function enqueue(int $driverId, int $vehicleId): array;
}
