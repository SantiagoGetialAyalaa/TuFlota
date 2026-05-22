<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverModel extends Model
{
    protected $table = 'drivers';

    protected $fillable = [
        'user_id',
        'license_number',
        'license_expires_at',
        'status',
        'is_available',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'license_expires_at' => 'date',
            'is_available' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'driver_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DriverDocumentModel::class, 'driver_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(TripModel::class, 'driver_id');
    }

    public function queueEntries(): HasMany
    {
        return $this->hasMany(QueueModel::class, 'driver_id');
    }

    public function debts(): HasMany
    {
        return $this->hasMany(DebtModel::class, 'driver_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(LocationModel::class, 'driver_id');
    }
}
