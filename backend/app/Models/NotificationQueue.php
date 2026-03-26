<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class NotificationQueue extends Model
{
    protected $table = 'notification_queue';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'channel',
        'template_name',
        'payload',
        'scheduled_at',
        'processed_at',
        'status',
        'attempts',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'scheduled_at' => 'datetime',
        'processed_at' => 'datetime',
        'attempts' => 'integer',
        'created_at' => 'datetime',
    ];

    const CHANNEL_WHATSAPP = 'whatsapp';
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_PUSH = 'push';
    const CHANNEL_SMS = 'sms';

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    protected static function booted(): void
    {
        static::creating(function (NotificationQueue $notification) {
            Log::info('Queueing notification', [
                'clinic_id' => $notification->clinic_id,
                'channel' => $notification->channel,
                'template_name' => $notification->template_name,
                'scheduled_at' => $notification->scheduled_at
            ]);
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeDue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                     ->where('scheduled_at', '<=', now());
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'processed_at' => now(),
        ]);
        Log::info('Notification sent', ['id' => $this->id]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error' => $error,
            'attempts' => $this->attempts + 1,
        ]);
        Log::error('Notification failed', ['id' => $this->id, 'error' => $error]);
    }

    public function canRetry(): bool
    {
        return $this->attempts < 3;
    }

    public static function schedule(
        int $clinicId,
        ?int $patientId,
        string $channel,
        string $templateName,
        array $payload,
        $scheduledAt = null
    ): self {
        return self::create([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'channel' => $channel,
            'template_name' => $templateName,
            'payload' => $payload,
            'scheduled_at' => $scheduledAt ?? now(),
            'status' => self::STATUS_PENDING,
            'attempts' => 0,
        ]);
    }
}
