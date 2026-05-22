<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservationModel extends Model
{
    protected $table = 'reservations';

    protected $fillable = [
        'user_id',
        'trip_id',
        'code',
        'status',
        'total_amount',
        'reserved_until',
        'paid_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'reserved_until' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(TripModel::class, 'trip_id');
    }

    public function seats(): BelongsToMany
    {
        return $this->belongsToMany(SeatModel::class, 'reservation_seats', 'reservation_id', 'seat_id')
            ->withPivot(['trip_id', 'price', 'status'])
            ->withTimestamps();
    }

    public function reservationSeats(): HasMany
    {
        return $this->hasMany(ReservationSeatModel::class, 'reservation_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentModel::class, 'reservation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MessageModel::class, 'reservation_id');
    }
}
