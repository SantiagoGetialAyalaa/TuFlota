<?php

declare(strict_types=1);

namespace App\Domain\Services;

interface PaymentGatewayInterface
{
    public function startPayment(float $amount, string $currency = 'COP'): array;
}
