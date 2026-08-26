<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_id',
        'status',
        'original_filename',
        'stored_path',
        'file_size',
        'log_excerpt',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'received_at' => 'datetime',
        ];
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }
}
