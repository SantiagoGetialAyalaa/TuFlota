<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverDocumentModel extends Model
{
    protected $table = 'driver_documents';

    protected $fillable = [
        'driver_id',
        'type',
        'number',
        'file_path',
        'status',
        'expires_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverModel::class, 'driver_id');
    }
}
