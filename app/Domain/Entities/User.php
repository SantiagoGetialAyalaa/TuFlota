<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly string $status,
        public readonly string $role,
        public readonly string $passwordHash,
        public readonly ?string $createdAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'role' => $this->role,
            'created_at' => $this->createdAt,
        ];
    }
}
