<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\GetUserReservationsUseCase;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(private readonly GetUserReservationsUseCase $getUserReservationsUseCase)
    {
    }

    public function reservations(int $user): JsonResponse
    {
        return response()->json(
            $this->getUserReservationsUseCase->execute($user),
        );
    }
}
