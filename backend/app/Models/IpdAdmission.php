<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class IpdAdmission extends Model
{
    protected $table = 'ipd_admissions';

    protected $guarded = [];

    protected $casts = [
        'consultant_doctor_ids' => 'array',
        'icd_codes'             => 'array',
        'admission_date'        => 'datetime',
        'discharge_date'        => 'datetime',
        'estimated_discharge_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (IpdAdmission $admission) {
            Log::info('Creating IPD admission', [
                'clinic_id'  => $admission->clinic_id,
                'patient_id' => $admission->patient_id,
                'bed_id'     => $admission->bed_id,
            ]);
        });

        static::created(function (IpdAdmission $admission) {
            Log::info('IPD admission created', [
                'id'               => $admission->id,
                'admission_number' => $admission->admission_number,
            ]);
        });

        static::updating(function (IpdAdmission $admission) {
            if ($admission->isDirty('status')) {
                Log::info('IPD admission status changed', [
                    'id'         => $admission->id,
                    'old_status' => $admission->getOriginal('status'),
                    'new_status' => $admission->status,
                ]);
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function admittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    public function primaryDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_doctor_id');
    }

    public function progressNotes(): HasMany
    {
        return $this->hasMany(IpdProgressNote::class)->orderByDesc('note_date');
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(IpdVital::class)->orderByDesc('recorded_at');
    }

    public function medicationOrders(): HasMany
    {
        return $this->hasMany(IpdMedicationOrder::class)->orderByDesc('created_at');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'admitted');
    }

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function scopeDischargedToday($query)
    {
        return $query->where('status', 'discharged')
                     ->whereDate('discharge_date', now()->toDateString());
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    public function getDaysAdmittedAttribute(): int
    {
        $start = $this->admission_date ?? now();
        $end   = $this->discharge_date ?? now();

        return (int) $start->diffInDays($end);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'admitted';
    }

    public function isDischarged(): bool
    {
        return $this->status === 'discharged';
    }
}
