<?php

declare(strict_types=1);

namespace App\Domain\Services;

interface AuthTokenServiceInterface
{
    public function issueForUserId(int $userId): string;
}
