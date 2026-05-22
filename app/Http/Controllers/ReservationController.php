<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\CancelReservationUseCase;
use App\Application\UseCases\CreateReservationUseCase;
use App\Http\Requests\CreateReservationRequest;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function __construct(
        private readonly CreateReservationUseCase $createReservationUseCase,
        private readonly CancelReservationUseCase $cancelReservationUseCase,
    ) {
    }

    public function store(CreateReservationRequest $request): JsonResponse
    {
        return response()->json(
            $this->createReservationUseCase->execute($request->validated()),
            201,
        );
    }

    public function destroy(int $reservation): JsonResponse
    {
        return response()->json(
            $this->cancelReservationUseCase->execute($reservation),
        );
    }
}
