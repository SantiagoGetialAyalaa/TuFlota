<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Reservation
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $tripId,
        public readonly string $code,
        public readonly string $status,
        public readonly float $totalAmount,
        public readonly ?string $reservedUntil = null,
        public readonly ?string $paidAt = null,
        public readonly ?string $cancelledAt = null,
        public readonly array $seats = [],
        public readonly array $payments = [],
        public readonly array $trip = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'trip_id' => $this->tripId,
            'code' => $this->code,
            'status' => $this->status,
            'total_amount' => $this->totalAmount,
            'reserved_until' => $this->reservedUntil,
            'paid_at' => $this->paidAt,
            'cancelled_at' => $this->cancelledAt,
            'seats' => $this->seats,
            'payments' => $this->payments,
            'trip' => $this->trip,
        ];
    }
}
