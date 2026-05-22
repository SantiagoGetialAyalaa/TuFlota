<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            if (! Schema::hasColumn('routes', 'code')) {
                $table->string('code')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('routes', 'distance_km')) {
                $table->decimal('distance_km', 8, 2)->nullable()->after('destination_longitude');
            }

            if (! Schema::hasColumn('routes', 'estimated_duration_minutes')) {
                $table->unsignedInteger('estimated_duration_minutes')->nullable()->after('distance_km');
            }

            if (! Schema::hasColumn('routes', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('base_price');
            }
        });

        Schema::table('schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('schedules', 'operating_days')) {
                $table->json('operating_days')->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'operating_days')) {
                $table->dropColumn('operating_days');
            }
        });

        Schema::table('routes', function (Blueprint $table) {
            foreach ([
                'code',
                'distance_km',
                'estimated_duration_minutes',
                'is_active',
            ] as $column) {
                if (Schema::hasColumn('routes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
