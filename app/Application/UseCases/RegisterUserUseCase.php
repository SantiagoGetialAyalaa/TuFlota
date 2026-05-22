<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Exceptions\BusinessRuleViolationException;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\PasswordHasherInterface;

class RegisterUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherInterface $hasher,
    ) {}

    public function execute(array $data): array
    {
        if ($this->users->existsByEmail($data['email'])) {
            throw new BusinessRuleViolationException('Ya existe un usuario registrado con ese correo.', 409);
        }

        $user = $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] ?? 'active',
            'role' => $data['role'] ?? 'user',
            'password' => $this->hasher->hash($data['password']),
        ]);

        return $user->toArray();
    }
}
