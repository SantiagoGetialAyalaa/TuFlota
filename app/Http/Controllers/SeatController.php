<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\AssignSeatUseCase;
use App\Application\UseCases\GetAvailableSeatsUseCase;
use App\Http\Requests\AssignSeatRequest;
use Illuminate\Http\JsonResponse;

class SeatController extends Controller
{
    public function __construct(
        private readonly GetAvailableSeatsUseCase $getAvailableSeatsUseCase,
        private readonly AssignSeatUseCase $assignSeatUseCase,
    ) {
    }

    public function available(int $trip): JsonResponse
    {
        return response()->json(
            $this->getAvailableSeatsUseCase->execute($trip),
        );
    }

    public function assign(AssignSeatRequest $request): JsonResponse
    {
        $payload = $request->validated();

        return response()->json(
            $this->assignSeatUseCase->execute((int) $payload['reservation_id'], $payload['seat_ids']),
        );
    }
}
