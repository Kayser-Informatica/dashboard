<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'active',
        'last_health_status',
        'last_health_message',
        'last_health_at',
        'last_backup_at',
        'external_ip',
        'last_ip_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_health_at' => 'datetime',
            'last_backup_at' => 'datetime',
            'last_ip_at' => 'datetime',
        ];
    }

    public function backupLogs(): HasMany
    {
        return $this->hasMany(BackupLog::class);
    }
}
