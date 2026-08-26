<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitored_service_id',
        'status',
        'original_filename',
        'stored_path',
        'file_size',
        'log_excerpt',
        'message',
        'duration_seconds',
        'ip',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'duration_seconds' => 'integer',
            'received_at' => 'datetime',
        ];
    }

    public function monitoredService(): BelongsTo
    {
        return $this->belongsTo(MonitoredService::class);
    }
}
