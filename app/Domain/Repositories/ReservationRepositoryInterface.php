<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Reservation;

interface ReservationRepositoryInterface
{
    public function createReservation(
        array $reservationData,
        array $seatIds,
        float $seatPrice,
        array $paymentData,
    ): Reservation;

    public function appendSeats(int $reservationId, int $tripId, array $seatIds, float $seatPrice): Reservation;

    public function findById(int $id): ?Reservation;

    public function getByUserId(int $userId): array;

    public function cancel(int $id): ?Reservation;

    public function countSeats(int $reservationId): int;
}
