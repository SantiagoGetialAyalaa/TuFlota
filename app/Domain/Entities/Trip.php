<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Trip
{
    public function __construct(
        public readonly int $id,
        public readonly int $scheduleId,
        public readonly int $vehicleId,
        public readonly int $driverId,
        public readonly string $status,
        public readonly int $currentPassengers,
        public readonly int $maxPassengers,
        public readonly ?string $departureDate = null,
        public readonly ?string $departureDateTime = null,
        public readonly ?string $estimatedArrivalDateTime = null,
        public readonly ?float $price = null,
        public readonly array $route = [],
        public readonly array $vehicle = [],
        public readonly array $driver = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'schedule_id' => $this->scheduleId,
            'vehicle_id' => $this->vehicleId,
            'driver_id' => $this->driverId,
            'status' => $this->status,
            'current_passengers' => $this->currentPassengers,
            'max_passengers' => $this->maxPassengers,
            'departure_date' => $this->departureDate,
            'departure_datetime' => $this->departureDateTime,
            'estimated_arrival_datetime' => $this->estimatedArrivalDateTime,
            'price' => $this->price,
            'route' => $this->route,
            'vehicle' => $this->vehicle,
            'driver' => $this->driver,
        ];
    }
}
