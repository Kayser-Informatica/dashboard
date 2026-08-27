<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class MonitoredService extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'slug',
        'expected_interval_minutes',
        'grace_period_minutes',
        'notification_emails',
        'last_ping_at',
        'last_status',
        'last_message',
        'last_duration_seconds',
        'last_ip',
        'is_in_alert',
        'last_alert_sent_at',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'expected_interval_minutes' => 'integer',
            'grace_period_minutes' => 'integer',
            'last_duration_seconds' => 'integer',
            'is_in_alert' => 'boolean',
            'active' => 'boolean',
            'last_ping_at' => 'datetime',
            'last_alert_sent_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class);
    }

    public function getNextExpectedAtAttribute(): ?Carbon
    {
        if (! $this->last_ping_at || ! $this->expected_interval_minutes) {
            return null;
        }

        $totalAllowedMinutes = $this->expected_interval_minutes + ($this->grace_period_minutes ?? 0);

        return $this->last_ping_at->copy()->addMinutes($totalAllowedMinutes);
    }

    public function getIsOverdueAttribute(): bool
    {
        if (! $this->last_ping_at || ! $this->expected_interval_minutes) {
            return false;
        }

        $deadline = $this->next_expected_at;

        return $deadline !== null && now()->greaterThan($deadline);
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->last_status === 'failed') {
            return 'failed';
        }

        if (! $this->last_ping_at) {
            return 'unknown';
        }

        if ($this->is_overdue) {
            return 'failed';
        }

        return $this->last_status ?? 'ok';
    }

    public function getNotificationEmailsArray(): array
    {
        if (! $this->notification_emails) {
            return [];
        }

        $rawEmails = preg_split('/[;,]/', $this->notification_emails);
        $cleanEmails = [];

        foreach ($rawEmails as $email) {
            $trimmed = trim($email);
            if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
                $cleanEmails[] = $trimmed;
            }
        }

        return array_unique($cleanEmails);
    }
}
