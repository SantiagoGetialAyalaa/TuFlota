<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripModel extends Model
{
    protected $table = 'trips';

    protected $fillable = [
        'schedule_id',
        'vehicle_id',
        'driver_id',
        'status',
        'current_passengers',
        'max_passengers',
        'departure_date',
        'departure_datetime',
        'estimated_arrival_datetime',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'current_passengers' => 'integer',
            'max_passengers' => 'integer',
            'departure_date' => 'date',
            'departure_datetime' => 'datetime',
            'estimated_arrival_datetime' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ScheduleModel::class, 'schedule_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverModel::class, 'driver_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ReservationModel::class, 'trip_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(RatingModel::class, 'trip_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(LocationModel::class, 'trip_id');
    }
}
