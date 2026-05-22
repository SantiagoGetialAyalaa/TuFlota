<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleModel extends Model
{
    protected $table = 'vehicles';

    protected $fillable = [
        'driver_id',
        'plate',
        'brand',
        'model',
        'type',
        'capacity',
        'amenities',
        'status',
        'last_inspection_at',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'amenities' => 'array',
            'last_inspection_at' => 'date',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverModel::class, 'driver_id');
    }

    public function seats(): HasMany
    {
        return $this->hasMany(SeatModel::class, 'vehicle_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(TripModel::class, 'vehicle_id');
    }
}
