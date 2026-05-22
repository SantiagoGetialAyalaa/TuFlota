<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\CreateTripUseCase;
use App\Application\UseCases\GetTripsUseCase;
use App\Http\Requests\CreateTripRequest;
use App\Http\Requests\SearchTripsRequest;
use Illuminate\Http\JsonResponse;

class TripController extends Controller
{
    public function __construct(
        private readonly GetTripsUseCase $getTripsUseCase,
        private readonly CreateTripUseCase $createTripUseCase,
    ) {
    }

    public function index(SearchTripsRequest $request): JsonResponse
    {
        return response()->json(
            $this->getTripsUseCase->execute($request->validated()),
        );
    }

    public function store(CreateTripRequest $request): JsonResponse
    {
        return response()->json(
            $this->createTripUseCase->execute($request->validated()),
            201,
        );
    }
}
