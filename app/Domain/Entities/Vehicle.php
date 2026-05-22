<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Vehicle
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $driverId,
        public readonly string $plate,
        public readonly string $model,
        public readonly string $type,
        public readonly int $capacity,
        public readonly string $status,
        public readonly ?string $brand = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'driver_id' => $this->driverId,
            'plate' => $this->plate,
            'model' => $this->model,
            'type' => $this->type,
            'capacity' => $this->capacity,
            'status' => $this->status,
            'brand' => $this->brand,
        ];
    }
}
