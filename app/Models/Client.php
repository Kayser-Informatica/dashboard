<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'api_token',
        'active',
    ];

    protected static function booted(): void
    {
        static::updating(function (Client $client): void {
            if ($client->isDirty('email')) {
                $client->email = $client->getOriginal('email');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public static function generateToken(): string
    {
        return 'clt_live_' . Str::random(40);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function monitoredServices(): HasMany
    {
        return $this->hasMany(MonitoredService::class);
    }
}
