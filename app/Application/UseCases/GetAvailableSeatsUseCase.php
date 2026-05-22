<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Exceptions\BusinessRuleViolationException;
use App\Domain\Repositories\SeatRepositoryInterface;
use App\Domain\Repositories\TripRepositoryInterface;

class GetAvailableSeatsUseCase
{
    public function __construct(
        private readonly TripRepositoryInterface $trips,
        private readonly SeatRepositoryInterface $seats,
    ) {
    }

    public function execute(int $tripId): array
    {
        $trip = $this->trips->findById($tripId);

        if (! $trip) {
            throw new BusinessRuleViolationException('El viaje solicitado no existe.', 404);
        }

        if ($trip->vehicleId <= 0) {
            throw new BusinessRuleViolationException('El viaje no tiene vehículo asignado.');
        }

        $this->seats->ensureVehicleSeats($trip->vehicleId);

        return array_map(
            static fn ($seat) => $seat->toArray(),
            $this->seats->getAvailableByTrip($tripId),
        );
    }
}
