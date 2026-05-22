<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'phone' => '3001234567',
            'status' => 'active',
            'role' => 'user',
            'password' => bcrypt('password'),
        ]);

        User::query()->firstOrCreate([
            'email' => 'empresa@example.com',
        ], [
            'name' => 'Empresa Demo',
            'phone' => '3007654321',
            'status' => 'active',
            'role' => 'company',
            'password' => bcrypt('password'),
        ]);

        $this->call(RouteSeeder::class);
    }
}
