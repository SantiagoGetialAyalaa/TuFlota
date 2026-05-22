<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Seat
{
    public function __construct(
        public readonly int $id,
        public readonly int $vehicleId,
        public readonly string $seatNumber,
        public readonly string $seatType,
        public readonly bool $isActive,
        public readonly bool $isAvailable = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicleId,
            'seat_number' => $this->seatNumber,
            'seat_type' => $this->seatType,
            'is_active' => $this->isActive,
            'is_available' => $this->isAvailable,
        ];
    }
}