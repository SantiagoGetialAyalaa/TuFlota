<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\Seat;
use App\Domain\Repositories\SeatRepositoryInterface;
use App\Infrastructure\Persistence\Models\ReservationSeatModel;
use App\Infrastructure\Persistence\Models\SeatModel;
use App\Infrastructure\Persistence\Models\TripModel;
use App\Infrastructure\Persistence\Models\VehicleModel;
use Illuminate\Database\Eloquent\Builder;

class SeatRepository implements SeatRepositoryInterface
{
    public function ensureVehicleSeats(int $vehicleId): array
    {
        $vehicle = VehicleModel::query()->with('seats')->findOrFail($vehicleId);
        $existingCount = $vehicle->seats->count();

        if ($existingCount < (int) $vehicle->capacity) {
            for ($index = $existingCount + 1; $index <= (int) $vehicle->capacity; $index++) {
                SeatModel::query()->create([
                    'vehicle_id' => $vehicleId,
                    'seat_number' => 'S'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                    'seat_type' => 'standard',
                    'is_active' => true,
                ]);
            }
        }

        return SeatModel::query()
            ->where('vehicle_id', $vehicleId)
            ->orderBy('seat_number')
            ->get()
            ->map(fn (SeatModel $seat) => $this->mapSeat($seat))
            ->all();
    }

    public function getAvailableByTrip(int $tripId): array
    {
        $trip = TripModel::query()->findOrFail($tripId);
        $reservedSeatIds = $this->reservedSeatIds($tripId);

        return SeatModel::query()
            ->where('vehicle_id', $trip->vehicle_id)
            ->where('is_active', true)
            ->orderBy('seat_number')
            ->get()
            ->map(fn (SeatModel $seat) => $this->mapSeat($seat, ! in_array($seat->id, $reservedSeatIds, true)))
            ->all();
    }

    public function findById(int $id): ?Seat
    {
        $seat = SeatModel::query()->find($id);

        return $seat ? $this->mapSeat($seat) : null;
    }

    public function belongsToVehicle(int $seatId, int $vehicleId): bool
    {
        return SeatModel::query()
            ->whereKey($seatId)
            ->where('vehicle_id', $vehicleId)
            ->exists();
    }

    public function isAvailableForTrip(int $tripId, int $seatId): bool
    {
        return ! in_array($seatId, $this->reservedSeatIds($tripId), true);
    }

    private function reservedSeatIds(int $tripId): array
    {
        return ReservationSeatModel::query()
            ->where('trip_id', $tripId)
            ->whereHas('reservation', function (Builder $builder) {
                $builder->where(function (Builder $reservationQuery) {
                    $reservationQuery
                        ->where('status', 'confirmed')
                        ->orWhere(function (Builder $pendingQuery) {
                            $pendingQuery
                                ->where('status', 'pending')
                                ->where(function (Builder $expiresQuery) {
                                    $expiresQuery
                                        ->whereNull('reserved_until')
                                        ->orWhere('reserved_until', '>', now());
                                });
                        });
                });
            })
            ->pluck('seat_id')
            ->map(fn ($seatId) => (int) $seatId)
            ->all();
    }

    private function mapSeat(SeatModel $seat, bool $isAvailable = true): Seat
    {
        return new Seat(
            id: (int) $seat->id,
            vehicleId: (int) $seat->vehicle_id,
            seatNumber: (string) $seat->seat_number,
            seatType: (string) $seat->seat_type,
            isActive: (bool) $seat->is_active,
            isAvailable: $isAvailable,
        );
    }
}
