<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('schedule_id')->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->after('vehicle_id')->constrained('drivers')->nullOnDelete();
            $table->dateTime('departure_datetime')->nullable()->after('departure_date');
            $table->dateTime('estimated_arrival_datetime')->nullable()->after('departure_datetime');
            $table->text('notes')->nullable()->after('estimated_arrival_datetime');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropConstrainedForeignId('driver_id');
            $table->dropColumn(['departure_datetime', 'estimated_arrival_datetime', 'notes']);
        });
    }
};
