<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueModel extends Model
{
    protected $table = 'queue';

    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'status',
        'joined_at',
        'processed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverModel::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_id');
    }
}
