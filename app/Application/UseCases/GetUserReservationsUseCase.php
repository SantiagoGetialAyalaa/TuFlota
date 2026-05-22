<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Repositories\ReservationRepositoryInterface;

class GetUserReservationsUseCase
{
    public function __construct(private readonly ReservationRepositoryInterface $reservations)
    {
    }

    public function execute(int $userId): array
    {
        return array_map(
            static fn ($reservation) => $reservation->toArray(),
            $this->reservations->getByUserId($userId),
        );
    }
}
