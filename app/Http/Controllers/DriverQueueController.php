<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\JoinQueueUseCase;
use App\Http\Requests\JoinQueueRequest;
use Illuminate\Http\JsonResponse;

class DriverQueueController extends Controller
{
    public function __construct(private readonly JoinQueueUseCase $joinQueueUseCase)
    {
    }

    public function store(JoinQueueRequest $request): JsonResponse
    {
        return response()->json(
            $this->joinQueueUseCase->execute((int) $request->validated()['driver_id']),
            201,
        );
    }
}
