<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_and_logs_in_a_user(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'API User',
            'email' => 'api@example.com',
            'phone' => '3001112233',
            'password' => 'secret123',
        ])->assertCreated();

        $this->postJson('/api/auth/login', [
            'email' => 'api@example.com',
            'password' => 'secret123',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'phone', 'status'],
            ]);
    }
}
