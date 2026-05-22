<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationSeatModel extends Model
{
    protected $table = 'reservation_seats';

    protected $fillable = [
        'reservation_id',
        'trip_id',
        'seat_id',
        'price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(ReservationModel::class, 'reservation_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(TripModel::class, 'trip_id');
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(SeatModel::class, 'seat_id');
    }
}
