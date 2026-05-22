<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Exceptions\BusinessRuleViolationException;
use App\Domain\Repositories\SeatRepositoryInterface;
use App\Domain\Repositories\TripRepositoryInterface;
use Carbon\Carbon;

class CreateTripUseCase
{
    public function __construct(
        private readonly TripRepositoryInterface $trips,
        private readonly SeatRepositoryInterface $seats,
    ) {
    }

    public function execute(array $data): array
    {
        if (empty($data['vehicle_id'])) {
            throw new BusinessRuleViolationException('No se puede crear un viaje sin vehículo asignado.');
        }

        if (! $this->trips->scheduleExists((int) $data['schedule_id'])) {
            throw new BusinessRuleViolationException('El horario seleccionado no existe o no está activo.');
        }

        if (! $this->trips->vehicleExists((int) $data['vehicle_id']) || ! $this->trips->vehicleIsActive((int) $data['vehicle_id'])) {
            throw new BusinessRuleViolationException('El vehículo asignado no existe o no está activo.');
        }

        if (empty($data['driver_id']) || ! $this->trips->driverExists((int) $data['driver_id'])) {
            throw new BusinessRuleViolationException('El conductor asignado no existe.');
        }

        $capacity = $this->trips->getVehicleCapacity((int) $data['vehicle_id']);

        if ($capacity <= 0) {
            throw new BusinessRuleViolationException('El vehículo asignado no tiene capacidad válida.');
        }

        $departureDateTime = Carbon::parse($data['departure_datetime']);

        $trip = $this->trips->create([
            'schedule_id' => (int) $data['schedule_id'],
            'vehicle_id' => (int) $data['vehicle_id'],
            'driver_id' => (int) $data['driver_id'],
            'status' => $data['status'] ?? 'scheduled',
            'current_passengers' => 0,
            'max_passengers' => $capacity,
            'departure_date' => $departureDateTime->toDateString(),
            'departure_datetime' => $departureDateTime->toDateTimeString(),
            'estimated_arrival_datetime' => ! empty($data['estimated_arrival_datetime'])
                ? Carbon::parse($data['estimated_arrival_datetime'])->toDateTimeString()
                : null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->seats->ensureVehicleSeats((int) $data['vehicle_id']);

        return $trip->toArray();
    }
}
