<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentModel extends Model
{
    protected $table = 'shipments';

    protected $fillable = [
        'user_id',
        'trip_id',
        'code',
        'sender_name',
        'recipient_name',
        'origin',
        'destination',
        'price',
        'weight_kg',
        'status',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'delivered_at' => 'datetime',
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
}
