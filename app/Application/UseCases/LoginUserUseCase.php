<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Exceptions\BusinessRuleViolationException;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\AuthTokenServiceInterface;
use App\Domain\Services\PasswordHasherInterface;

class LoginUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherInterface $hasher,
        private readonly AuthTokenServiceInterface $tokenService,
    ) {
    }

    public function execute(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        if (! $user || ! $this->hasher->check($password, $user->passwordHash)) {
            throw new BusinessRuleViolationException('Credenciales inválidas.', 401);
        }

        return [
            'token' => $this->tokenService->issueForUserId($user->id),
            'user' => $user->toArray(),
        ];
    }
}
