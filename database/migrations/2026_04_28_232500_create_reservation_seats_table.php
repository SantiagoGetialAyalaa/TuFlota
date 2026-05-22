<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('seat_id')->constrained('seats')->cascadeOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status')->default('held');
            $table->timestamps();

            $table->unique(['reservation_id', 'seat_id']);
            $table->unique(['trip_id', 'seat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_seats');
    }
};
