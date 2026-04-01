<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

class Appointment extends Model
{
    use SoftDeletes;

    protected $table = 'appointments';

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'doctor_id',
        'service_id',
        'room_id',
        'equipment_id',
        'location_id',
        'scheduled_at',
        'duration_mins',
        'status',
        'token_number',
        'booking_source',
        'appointment_type',
        'specialty',
        'advance_paid',
        'razorpay_order_id',
        'razorpay_payment_id',
        'confirmation_sent_at',
        'reminder_24h_sent_at',
        'reminder_2h_sent_at',
        'pre_visit_answers',
        'pre_visit_data',
        'pre_visit_token',
        'notes',
        'teleconsult_meeting_url',
        'rescheduled_from_id',
        'cancelled_reason',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_mins' => 'integer',
        'token_number' => 'integer',
        'advance_paid' => 'decimal:2',
        'confirmation_sent_at' => 'datetime',
        'reminder_24h_sent_at' => 'datetime',
        'reminder_2h_sent_at' => 'datetime',
        'pre_visit_answers' => 'array',
        'pre_visit_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_BOOKED = 'booked';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CHECKED_IN = 'checked_in';
    const STATUS_IN_CONSULTATION = 'in_consultation';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';
    const STATUS_RESCHEDULED = 'rescheduled';

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment) {
            Log::info('Creating appointment', [
                'clinic_id' => $appointment->clinic_id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'scheduled_at' => $appointment->scheduled_at
            ]);
        });

        static::created(function (Appointment $appointment) {
            Log::info('Appointment created', ['id' => $appointment->id, 'status' => $appointment->status]);
        });

        static::updating(function (Appointment $appointment) {
            Log::info('Updating appointment', ['id' => $appointment->id, 'changes' => $appointment->getDirty()]);
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class, 'service_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ClinicRoom::class, 'room_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(ClinicEquipment::class, 'equipment_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ClinicLocation::class);
    }

    public function visit(): HasOne
    {
        return $this->hasOne(Visit::class);
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'rescheduled_from_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('scheduled_at', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', now()->toDateString());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>=', now())
                     ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_COMPLETED]);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeNeedingReminder24h($query)
    {
        return $query->whereNull('reminder_24h_sent_at')
                     ->where('scheduled_at', '<=', now()->addHours(24))
                     ->where('scheduled_at', '>', now())
                     ->whereIn('status', [self::STATUS_BOOKED, self::STATUS_CONFIRMED]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isToday(): bool
    {
        return $this->scheduled_at->isToday();
    }

    public function isPast(): bool
    {
        return $this->scheduled_at->isPast();
    }

    public function canCheckIn(): bool
    {
        return in_array($this->status, [self::STATUS_BOOKED, self::STATUS_CONFIRMED]);
    }

    public function canCancel(): bool
    {
        return !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function markAsCheckedIn(): void
    {
        $this->update(['status' => self::STATUS_CHECKED_IN]);
        Log::info('Appointment checked in', ['id' => $this->id]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
        Log::info('Appointment completed', ['id' => $this->id]);
    }
}
