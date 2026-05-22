<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\Reservation;
use App\Domain\Exceptions\BusinessRuleViolationException;
use App\Domain\Repositories\ReservationRepositoryInterface;
use App\Infrastructure\Persistence\Models\PaymentModel;
use App\Infrastructure\Persistence\Models\ReservationModel;
use App\Infrastructure\Persistence\Models\ReservationSeatModel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReservationRepository implements ReservationRepositoryInterface
{
    public function createReservation(
        array $reservationData,
        array $seatIds,
        float $seatPrice,
        array $paymentData,
    ): Reservation {
        try {
            return DB::transaction(function () use ($reservationData, $seatIds, $seatPrice, $paymentData) {
                $reservation = ReservationModel::query()->create($reservationData);

                foreach ($seatIds as $seatId) {
                    ReservationSeatModel::query()->create([
                        'reservation_id' => $reservation->id,
                        'trip_id' => $reservationData['trip_id'],
                        'seat_id' => $seatId,
                        'price' => $seatPrice,
                        'status' => 'held',
                    ]);
                }

                PaymentModel::query()->create(array_merge($paymentData, [
                    'reservation_id' => $reservation->id,
                ]));

                return $this->loadReservation($reservation->id);
            });
        } catch (QueryException $exception) {
            throw $this->mapSeatConflict($exception);
        }
    }

    public function appendSeats(int $reservationId, int $tripId, array $seatIds, float $seatPrice): Reservation
    {
        try {
            return DB::transaction(function () use ($reservationId, $tripId, $seatIds, $seatPrice) {
                foreach ($seatIds as $seatId) {
                    ReservationSeatModel::query()->create([
                        'reservation_id' => $reservationId,
                        'trip_id' => $tripId,
                        'seat_id' => $seatId,
                        'price' => $seatPrice,
                        'status' => 'held',
                    ]);
                }

                $reservation = ReservationModel::query()->findOrFail($reservationId);
                $reservation->update([
                    'total_amount' => (float) $reservation->total_amount + ($seatPrice * count($seatIds)),
                ]);

                return $this->loadReservation($reservationId);
            });
        } catch (QueryException $exception) {
            throw $this->mapSeatConflict($exception);
        }
    }

    public function findById(int $id): ?Reservation
    {
        $reservation = ReservationModel::query()
            ->with(['trip.schedule.route', 'reservationSeats.seat', 'payments'])
            ->find($id);

        return $reservation ? $this->mapReservation($reservation) : null;
    }

    public function getByUserId(int $userId): array
    {
        return ReservationModel::query()
            ->with(['trip.schedule.route', 'reservationSeats.seat', 'payments'])
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->map(fn (ReservationModel $reservation) => $this->mapReservation($reservation))
            ->all();
    }

    public function cancel(int $id): ?Reservation
    {
        return DB::transaction(function () use ($id) {
            $reservation = ReservationModel::query()->find($id);

            if (! $reservation) {
                return null;
            }

            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $reservation->reservationSeats()->update([
                'status' => 'cancelled',
            ]);

            $reservation->payments()
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                ]);

            return $this->loadReservation($reservation->id);
        });
    }

    public function countSeats(int $reservationId): int
    {
        return ReservationSeatModel::query()
            ->where('reservation_id', $reservationId)
            ->where('status', '!=', 'cancelled')
            ->count();
    }

    private function loadReservation(int $reservationId): Reservation
    {
        $reservation = ReservationModel::query()
            ->with(['trip.schedule.route', 'reservationSeats.seat', 'payments'])
            ->findOrFail($reservationId);

        return $this->mapReservation($reservation);
    }

    private function mapReservation(ReservationModel $reservation): Reservation
    {
        $route = $reservation->trip?->schedule?->route;

        return new Reservation(
            id: (int) $reservation->id,
            userId: (int) $reservation->user_id,
            tripId: (int) $reservation->trip_id,
            code: (string) $reservation->code,
            status: (string) $reservation->status,
            totalAmount: (float) $reservation->total_amount,
            reservedUntil: $reservation->reserved_until?->toDateTimeString(),
            paidAt: $reservation->paid_at?->toDateTimeString(),
            cancelledAt: $reservation->cancelled_at?->toDateTimeString(),
            seats: $reservation->reservationSeats->map(function ($seatReservation) {
                return [
                    'seat_id' => (int) $seatReservation->seat_id,
                    'seat_number' => $seatReservation->seat?->seat_number,
                    'price' => (float) $seatReservation->price,
                    'status' => (string) $seatReservation->status,
                ];
            })->values()->all(),
            payments: $reservation->payments->map(function ($payment) {
                return [
                    'id' => (int) $payment->id,
                    'provider' => (string) $payment->provider,
                    'external_reference' => (string) $payment->external_reference,
                    'amount' => (float) $payment->amount,
                    'currency' => (string) $payment->currency,
                    'status' => (string) $payment->status,
                    'paid_at' => $payment->paid_at?->toDateTimeString(),
                ];
            })->values()->all(),
            trip: $reservation->trip ? [
                'id' => (int) $reservation->trip->id,
                'status' => (string) $reservation->trip->status,
                'departure_datetime' => $reservation->trip->departure_datetime?->toDateTimeString(),
                'route' => $route ? [
                    'id' => (int) $route->id,
                    'code' => (string) $route->code,
                    'origin' => (string) $route->origin,
                    'destination' => (string) $route->destination,
                ] : null,
            ] : [],
        );
    }

    private function mapSeatConflict(QueryException $exception): BusinessRuleViolationException
    {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'unique') || str_contains($message, 'duplicate')) {
            return new BusinessRuleViolationException('No se puede reservar un asiento ocupado.');
        }

        return new BusinessRuleViolationException('No fue posible guardar la reserva.');
    }
}
