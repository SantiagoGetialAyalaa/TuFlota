<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Exceptions\BusinessRuleViolationException;
use App\Domain\Repositories\ReservationRepositoryInterface;
use App\Domain\Repositories\SeatRepositoryInterface;
use App\Domain\Repositories\TripRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\PaymentGatewayInterface;
use Illuminate\Support\Str;

class CreateReservationUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TripRepositoryInterface $trips,
        private readonly SeatRepositoryInterface $seats,
        private readonly ReservationRepositoryInterface $reservations,
        private readonly PaymentGatewayInterface $payments,
    ) {
    }

    public function execute(array $data): array
    {
        if (! $this->users->findById((int) $data['user_id'])) {
            throw new BusinessRuleViolationException('El usuario seleccionado no existe.');
        }

        $trip = $this->trips->findActiveById((int) $data['trip_id']);

        if (! $trip) {
            throw new BusinessRuleViolationException('No se puede crear una reserva para un viaje inactivo.');
        }

        if ($trip->vehicleId <= 0) {
            throw new BusinessRuleViolationException('El viaje no tiene un vehículo asignado.');
        }

        $seatIds = array_values(array_unique(array_map('intval', $data['seat_ids'] ?? [])));

        if ($seatIds === []) {
            throw new BusinessRuleViolationException('Debe seleccionar al menos un asiento.');
        }

        if (($trip->currentPassengers + count($seatIds)) > $trip->maxPassengers) {
            throw new BusinessRuleViolationException('El viaje no tiene cupos suficientes.');
        }

        $this->seats->ensureVehicleSeats($trip->vehicleId);

        foreach ($seatIds as $seatId) {
            if (! $this->seats->belongsToVehicle($seatId, $trip->vehicleId)) {
                throw new BusinessRuleViolationException('Uno de los asientos no pertenece al vehículo del viaje.');
            }

            if (! $this->seats->isAvailableForTrip($trip->id, $seatId)) {
                throw new BusinessRuleViolationException('No se puede reservar un asiento ocupado.');
            }
        }

        $seatPrice = (float) ($trip->price ?? $trip->route['base_price'] ?? 0);
        $totalAmount = $seatPrice * count($seatIds);
        $reservedMinutes = (int) ($data['reserved_minutes'] ?? 15);

        $reservation = $this->reservations->createReservation(
            reservationData: [
                'user_id' => (int) $data['user_id'],
                'trip_id' => $trip->id,
                'code' => 'RSV-'.Str::upper(Str::random(8)),
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'reserved_until' => now()->addMinutes($reservedMinutes),
            ],
            seatIds: $seatIds,
            seatPrice: $seatPrice,
            paymentData: $this->payments->startPayment($totalAmount),
        );

        $this->trips->incrementPassengers($trip->id, count($seatIds));

        return $reservation->toArray();
    }
}
