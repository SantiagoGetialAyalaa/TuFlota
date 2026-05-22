<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RouteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_allow_reserving_an_occupied_seat(): void
    {
        $this->seed(RouteSeeder::class);

        $user = User::factory()->create();
        $trip = Trip::query()->firstOrFail();

        $seatId = $this->getJson('/api/seats/trips/'.$trip->id)
            ->assertOk()
            ->json('0.id');

        $this->postJson('/api/reservations', [
            'user_id' => $user->id,
            'trip_id' => $trip->id,
            'seat_ids' => [$seatId],
        ])->assertCreated();

        $this->postJson('/api/reservations', [
            'user_id' => $user->id,
            'trip_id' => $trip->id,
            'seat_ids' => [$seatId],
        ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'No se puede reservar un asiento ocupado.',
            ]);
    }
}
