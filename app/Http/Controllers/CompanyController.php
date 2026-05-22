<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Models\ReservationModel;
use App\Infrastructure\Persistence\Models\RouteModel;
use App\Infrastructure\Persistence\Models\ScheduleModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function routes(): JsonResponse
    {
        $routes = RouteModel::query()
            ->with('schedules')
            ->orderBy('origin')
            ->orderBy('destination')
            ->get()
            ->map(fn (RouteModel $route) => [
                'id' => (int) $route->id,
                'code' => (string) $route->code,
                'origin' => (string) $route->origin,
                'destination' => (string) $route->destination,
                'origin_latitude' => $route->origin_latitude !== null ? (float) $route->origin_latitude : null,
                'origin_longitude' => $route->origin_longitude !== null ? (float) $route->origin_longitude : null,
                'destination_latitude' => $route->destination_latitude !== null ? (float) $route->destination_latitude : null,
                'destination_longitude' => $route->destination_longitude !== null ? (float) $route->destination_longitude : null,
                'distance_km' => $route->distance_km !== null ? (float) $route->distance_km : null,
                'estimated_duration_minutes' => $route->estimated_duration_minutes !== null ? (int) $route->estimated_duration_minutes : null,
                'base_price' => $route->base_price !== null ? (float) $route->base_price : null,
                'is_active' => (bool) $route->is_active,
                'schedules' => $route->schedules->map(fn (ScheduleModel $schedule) => [
                    'id' => (int) $schedule->id,
                    'departure_time' => (string) $schedule->departure_time,
                    'estimated_arrival_time' => (string) $schedule->estimated_arrival_time,
                    'price' => (float) $schedule->price,
                    'is_active' => (bool) $schedule->is_active,
                ])->values()->all(),
            ]);

        return response()->json($routes);
    }

    public function storeRoute(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'origin' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'origin_latitude' => ['nullable', 'numeric'],
            'origin_longitude' => ['nullable', 'numeric'],
            'destination_latitude' => ['nullable', 'numeric'],
            'destination_longitude' => ['nullable', 'numeric'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'departure_time' => ['required', 'date_format:H:i'],
            'estimated_arrival_time' => ['required', 'date_format:H:i'],
            'operating_days' => ['nullable', 'array'],
            'operating_days.*' => ['string'],
        ]);

        $code = $data['code'] ?? strtoupper(substr($data['origin'], 0, 3).'-'.substr($data['destination'], 0, 3).'-'.random_int(100, 999));

        $route = RouteModel::query()->create([
            'code' => $code,
            'origin' => $data['origin'],
            'origin_latitude' => $data['origin_latitude'] ?? null,
            'origin_longitude' => $data['origin_longitude'] ?? null,
            'destination' => $data['destination'],
            'destination_latitude' => $data['destination_latitude'] ?? null,
            'destination_longitude' => $data['destination_longitude'] ?? null,
            'distance_km' => $data['distance_km'] ?? null,
            'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? null,
            'base_price' => $data['base_price'],
            'is_active' => true,
        ]);

        $schedule = ScheduleModel::query()->create([
            'route_id' => $route->id,
            'departure_time' => $data['departure_time'],
            'estimated_arrival_time' => $data['estimated_arrival_time'],
            'price' => $data['base_price'],
            'operating_days' => $data['operating_days'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true,
        ]);

        return response()->json([
            'route' => [
                'id' => (int) $route->id,
                'code' => (string) $route->code,
                'origin' => (string) $route->origin,
                'destination' => (string) $route->destination,
                'base_price' => (float) $route->base_price,
            ],
            'schedule' => [
                'id' => (int) $schedule->id,
                'departure_time' => (string) $schedule->departure_time,
                'estimated_arrival_time' => (string) $schedule->estimated_arrival_time,
                'price' => (float) $schedule->price,
            ],
        ], 201);
    }

    public function passengers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'trip_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'cancelled'])],
        ]);

        $query = ReservationModel::query()
            ->with(['user', 'trip.schedule.route', 'reservationSeats.seat', 'payments'])
            ->latest();

        if (! empty($data['trip_id'])) {
            $query->where('trip_id', (int) $data['trip_id']);
        }

        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        return response()->json($query->get()->map(function (ReservationModel $reservation) {
            $route = $reservation->trip?->schedule?->route;

            return [
                'id' => (int) $reservation->id,
                'code' => (string) $reservation->code,
                'status' => (string) $reservation->status,
                'total_amount' => (float) $reservation->total_amount,
                'paid_at' => $reservation->paid_at?->toDateTimeString(),
                'passenger' => [
                    'id' => (int) $reservation->user_id,
                    'name' => $reservation->user?->name,
                    'email' => $reservation->user?->email,
                    'phone' => $reservation->user?->phone,
                ],
                'trip' => [
                    'id' => (int) $reservation->trip_id,
                    'departure_datetime' => $reservation->trip?->departure_datetime?->toDateTimeString(),
                    'route' => $route ? [
                        'origin' => (string) $route->origin,
                        'destination' => (string) $route->destination,
                    ] : null,
                ],
                'seats' => $reservation->reservationSeats->map(fn ($seatReservation) => [
                    'seat_number' => $seatReservation->seat?->seat_number,
                    'status' => (string) $seatReservation->status,
                ])->values()->all(),
            ];
        }));
    }
}
