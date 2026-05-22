<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Repositories\TripRepositoryInterface;

class GetTripsUseCase
{
    public function __construct(private readonly TripRepositoryInterface $trips)
    {
    }

    public function execute(array $filters = []): array
    {
        return array_map(
            static fn ($trip) => $trip->toArray(),
            $this->trips->search($filters),
        );
    }
}
