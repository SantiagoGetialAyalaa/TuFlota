<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Exceptions\BusinessRuleViolationException;
use App\Domain\Repositories\ReservationRepositoryInterface;
use App\Domain\Repositories\TripRepositoryInterface;

class CancelReservationUseCase
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservations,
        private readonly TripRepositoryInterface $trips,
    ) {
    }

    public function execute(int $reservationId): array
    {
        $reservation = $this->reservations->findById($reservationId);

        if (! $reservation) {
            throw new BusinessRuleViolationException('La reserva indicada no existe.', 404);
        }

        if ($reservation->status === 'cancelled') {
            throw new BusinessRuleViolationException('La reserva ya fue cancelada.');
        }

        $seatCount = $this->reservations->countSeats($reservationId);
        $cancelledReservation = $this->reservations->cancel($reservationId);

        if (! $cancelledReservation) {
            throw new BusinessRuleViolationException('No se pudo cancelar la reserva.', 500);
        }

        $this->trips->decrementPassengers($reservation->tripId, $seatCount);

        return $cancelledReservation->toArray();
    }
}
