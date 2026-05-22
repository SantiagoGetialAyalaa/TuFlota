<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Driver
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $licenseNumber,
        public readonly string $status,
        public readonly ?string $licenseExpiresAt = null,
        public readonly bool $isAvailable = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'license_number' => $this->licenseNumber,
            'status' => $this->status,
            'license_expires_at' => $this->licenseExpiresAt,
            'is_available' => $this->isAvailable,
        ];
    }
}
