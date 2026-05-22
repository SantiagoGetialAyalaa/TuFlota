<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Exceptions\BusinessRuleViolationException;
use App\Domain\Repositories\DriverRepositoryInterface;
use App\Domain\Repositories\QueueRepositoryInterface;

class JoinQueueUseCase
{
    public function __construct(
        private readonly DriverRepositoryInterface $drivers,
        private readonly QueueRepositoryInterface $queue,
    ) {
    }

    public function execute(int $driverId): array
    {
        if (! $this->drivers->exists($driverId)) {
            throw new BusinessRuleViolationException('El conductor indicado no existe.', 404);
        }

        if (! $this->drivers->isEligibleForQueue($driverId)) {
            throw new BusinessRuleViolationException(
                'El conductor no cumple las condiciones para entrar en la cola FIFO.',
            );
        }

        $vehicleId = $this->drivers->getActiveVehicleId($driverId);

        if (! $vehicleId) {
            throw new BusinessRuleViolationException('El conductor no tiene un vehículo activo asignado.');
        }

        return $this->queue->enqueue($driverId, $vehicleId);
    }
}
