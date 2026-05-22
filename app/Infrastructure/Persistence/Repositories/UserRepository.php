<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\Reservation;
use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Models\ReservationModel;
use App\Infrastructure\Persistence\Models\UserModel;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $attributes): User
    {
        $user = UserModel::query()->create($attributes);

        return $this->mapUser($user);
    }

    public function findById(int $id): ?User
    {
        $user = UserModel::query()->find($id);

        return $user ? $this->mapUser($user) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $user = UserModel::query()->where('email', $email)->first();

        return $user ? $this->mapUser($user) : null;
    }

    public function existsByEmail(string $email): bool
    {
        return UserModel::query()->where('email', $email)->exists();
    }

    public function getReservations(int $userId): array
    {
        return ReservationModel::query()
            ->with(['trip.schedule.route', 'reservationSeats.seat', 'payments'])
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->map(fn (ReservationModel $reservation) => $this->mapReservation($reservation))
            ->all();
    }

    private function mapUser(UserModel $user): User
    {
        return new User(
            id: (int) $user->id,
            name: (string) $user->name,
            email: (string) $user->email,
            phone: $user->phone,
            status: (string) $user->status,
            role: (string) ($user->role ?? 'user'),
            passwordHash: (string) $user->getAuthPassword(),
            createdAt: $user->created_at?->toDateTimeString(),
        );
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
}
