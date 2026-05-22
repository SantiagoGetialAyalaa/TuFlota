<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Services\PaymentGatewayInterface;
use Illuminate\Support\Str;

class MockPaymentGatewayService implements PaymentGatewayInterface
{
    public function startPayment(float $amount, string $currency = 'COP'): array
    {
        return [
            'provider' => 'mock_gateway',
            'external_reference' => (string) Str::uuid(),
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'metadata' => [
                'checkout_url' => 'https://payments.example.test/mock-checkout',
            ],
        ];
    }
}
