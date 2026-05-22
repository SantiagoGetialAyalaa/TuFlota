<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Services\PasswordHasherInterface;
use Illuminate\Support\Facades\Hash;

class BcryptPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainText): string
    {
        return Hash::make($plainText);
    }

    public function check(string $plainText, string $hashedValue): bool
    {
        return Hash::check($plainText, $hashedValue);
    }
}
