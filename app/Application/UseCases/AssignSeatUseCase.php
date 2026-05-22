<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Exceptions\BusinessRuleViolationException;
use App\Domain\Repositories\ReservationRepositoryInterface;
use App\Domain\Repositories\SeatRepositoryInterface;
use App\Domain\Repositories\TripRepositoryInterface;

class AssignSeatUseCase
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservations,
        private readonly TripRepositoryInterface $trips,
        private readonly SeatRepositoryInterface $seats,
    ) {
    }

    public function execute(int $reservationId, array $seatIds): array
    {
        $reservation = $this->reservations->findById($reservationId);

        if (! $reservation) {
            throw new BusinessRuleViolationException('La reserva indicada no existe.', 404);
        }

        $trip = $this->trips->findActiveById($reservation->tripId);

        if (! $trip) {
            throw new BusinessRuleViolationException('No se pueden asignar asientos a un viaje inactivo.');
        }

        if (($trip->currentPassengers + count($seatIds)) > $trip->maxPassengers) {
            throw new BusinessRuleViolationException('El viaje no tiene cupos suficientes.');
        }

        foreach ($seatIds as $seatId) {
            if (! $this->seats->belongsToVehicle((int) $seatId, $trip->vehicleId)) {
                throw new BusinessRuleViolationException('Uno de los asientos no pertenece al vehículo del viaje.');
            }

            if (! $this->seats->isAvailableForTrip($trip->id, (int) $seatId)) {
                throw new BusinessRuleViolationException('No se puede asignar un asiento ocupado.');
            }
        }

        $seatPrice = (float) ($trip->price ?? $trip->route['base_price'] ?? 0);
        $updatedReservation = $this->reservations->appendSeats($reservationId, $trip->id, $seatIds, $seatPrice);

        $this->trips->incrementPassengers($trip->id, count($seatIds));

        return $updatedReservation->toArray();
    }
}
