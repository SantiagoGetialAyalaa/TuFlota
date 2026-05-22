<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Models\ReservationModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function pay(int $reservation): JsonResponse
    {
        $reservationModel = DB::transaction(function () use ($reservation) {
            $reservationModel = ReservationModel::query()
                ->with(['trip.schedule.route', 'reservationSeats.seat', 'payments'])
                ->findOrFail($reservation);

            $reservationModel->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $reservationModel->reservationSeats()->update([
                'status' => 'confirmed',
            ]);

            $reservationModel->payments()->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return $reservationModel->refresh()->load(['trip.schedule.route', 'reservationSeats.seat', 'payments']);
        });

        return response()->json([
            'id' => (int) $reservationModel->id,
            'code' => (string) $reservationModel->code,
            'status' => (string) $reservationModel->status,
            'total_amount' => (float) $reservationModel->total_amount,
            'paid_at' => $reservationModel->paid_at?->toDateTimeString(),
            'seats' => $reservationModel->reservationSeats->map(fn ($seatReservation) => [
                'seat_number' => $seatReservation->seat?->seat_number,
                'status' => (string) $seatReservation->status,
            ])->values()->all(),
        ]);
    }
}
