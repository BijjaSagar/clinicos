<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class WhatsappMessage extends Model
{
    protected $table = 'whatsapp_messages';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'direction',
        'wa_message_id',
        'wa_phone_from',
        'wa_phone_to',
        'template_name',
        'message_type',
        'body',
        'media_url',
        'trigger_type',
        'related_id',
        'status',
        'error_code',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    const DIRECTION_OUTBOUND = 'outbound';
    const DIRECTION_INBOUND = 'inbound';

    const STATUS_QUEUED = 'queued';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';
    const STATUS_FAILED = 'failed';
    const STATUS_ERROR = 'error';

    const TRIGGER_APPOINTMENT_CONFIRMATION = 'appointment_confirmation';
    const TRIGGER_REMINDER_24H = 'reminder_24h';
    const TRIGGER_REMINDER_2H = 'reminder_2h';
    const TRIGGER_PRESCRIPTION = 'prescription';
    const TRIGGER_PAYMENT_LINK = 'payment_link';
    const TRIGGER_RECALL = 'recall';
    const TRIGGER_HEP = 'hep';
    const TRIGGER_RESULT = 'result';
    const TRIGGER_BIRTHDAY = 'birthday';
    const TRIGGER_MANUAL = 'manual';
    const TRIGGER_INBOUND_REPLY = 'inbound_reply';

    protected static function booted(): void
    {
        static::creating(function (WhatsappMessage $message) {
            Log::info('Creating WhatsApp message', [
                'clinic_id' => $message->clinic_id,
                'direction' => $message->direction,
                'wa_phone_to' => $message->wa_phone_to,
                'trigger_type' => $message->trigger_type
            ]);
        });

        static::created(function (WhatsappMessage $message) {
            Log::info('WhatsApp message created', [
                'id' => $message->id,
                'status' => $message->status
            ]);
        });

        static::updating(function (WhatsappMessage $message) {
            if ($message->isDirty('status')) {
                Log::info('WhatsApp message status changed', [
                    'id' => $message->id,
                    'old_status' => $message->getOriginal('status'),
                    'new_status' => $message->status
                ]);
            }
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

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    public function scopeByTrigger($query, string $trigger)
    {
        return $query->where('trigger_type', $trigger);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_ERROR]);
    }

    public function isOutbound(): bool
    {
        return $this->direction === self::DIRECTION_OUTBOUND;
    }

    public function isInbound(): bool
    {
        return $this->direction === self::DIRECTION_INBOUND;
    }

    public function wasDelivered(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_READ]);
    }

    public function wasRead(): bool
    {
        return $this->status === self::STATUS_READ;
    }

    public function hasFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_ERROR]);
    }

    public function markAsSent(string $waMessageId): void
    {
        $this->update([
            'wa_message_id' => $waMessageId,
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    public function markAsRead(): void
    {
        $this->update([
            'status' => self::STATUS_READ,
            'read_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorCode, string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);
        
        Log::error('WhatsApp message failed', [
            'id' => $this->id,
            'error_code' => $errorCode,
            'error_message' => $errorMessage
        ]);
    }
}
