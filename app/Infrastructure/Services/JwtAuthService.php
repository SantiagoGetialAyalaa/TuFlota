<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Services\AuthTokenServiceInterface;
use App\Infrastructure\Persistence\Models\UserModel;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAuthService implements AuthTokenServiceInterface
{
    public function issueForUserId(int $userId): string
    {
        $user = UserModel::query()->findOrFail($userId);

        return JWTAuth::fromUser($user);
    }
}
