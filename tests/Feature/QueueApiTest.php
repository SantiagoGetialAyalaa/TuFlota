<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Models\DriverModel;
use App\Models\User;
use Database\Seeders\RouteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_a_driver_that_does_not_meet_fifo_requirements(): void
    {
        $user = User::factory()->create();

        $driver = DriverModel::query()->create([
            'user_id' => $user->id,
            'license_number' => 'NO-DOCS-001',
            'license_expires_at' => now()->addMonth()->toDateString(),
            'status' => 'approved',
            'is_available' => true,
            'approved_at' => now(),
        ]);

        $this->postJson('/api/drivers/queue', [
            'driver_id' => $driver->id,
        ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'El conductor no cumple las condiciones para entrar en la cola FIFO.',
            ]);
    }

    public function test_it_allows_an_eligible_driver_to_join_the_fifo_queue(): void
    {
        $this->seed(RouteSeeder::class);

        $driver = DriverModel::query()->firstOrFail();

        $this->postJson('/api/drivers/queue', [
            'driver_id' => $driver->id,
        ])
            ->assertCreated()
            ->assertJson([
                'driver_id' => $driver->id,
                'status' => 'waiting',
                'position' => 1,
            ]);
    }
}
