<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleModel extends Model
{
    protected $table = 'schedules';

    protected $fillable = [
        'route_id',
        'departure_time',
        'estimated_arrival_time',
        'price',
        'operating_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'operating_days' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(RouteModel::class, 'route_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(TripModel::class, 'schedule_id');
    }
}
