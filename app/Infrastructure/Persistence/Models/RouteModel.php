<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RouteModel extends Model
{
    protected $table = 'routes';

    protected $fillable = [
        'code',
        'origin',
        'origin_latitude',
        'origin_longitude',
        'destination',
        'destination_latitude',
        'destination_longitude',
        'distance_km',
        'estimated_duration_minutes',
        'base_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'origin_latitude' => 'decimal:7',
            'origin_longitude' => 'decimal:7',
            'destination_latitude' => 'decimal:7',
            'destination_longitude' => 'decimal:7',
            'base_price' => 'decimal:2',
            'estimated_duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ScheduleModel::class, 'route_id');
    }
}
