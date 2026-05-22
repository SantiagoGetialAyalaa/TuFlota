<?php

declare(strict_types=1);

namespace App\Domain\Services;

interface PasswordHasherInterface
{
    public function hash(string $plainText): string;

    public function check(string $plainText, string $hashedValue): bool;
}
