<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatModel extends Model
{
    protected $table = 'seats';

    protected $fillable = [
        'vehicle_id',
        'seat_number',
        'seat_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_id');
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(ReservationModel::class, 'reservation_seats', 'seat_id', 'reservation_id')
            ->withPivot(['trip_id', 'price', 'status'])
            ->withTimestamps();
    }

    public function reservationSeats(): HasMany
    {
        return $this->hasMany(ReservationSeatModel::class, 'seat_id');
    }
}
