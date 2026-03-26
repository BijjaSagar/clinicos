<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class PhysioTreatmentPlan extends Model
{
    protected $table = 'physio_treatment_plans';

    protected $fillable = [
        'patient_id',
        'clinic_id',
        'visit_id',
        'diagnosis',
        'referring_doctor',
        'total_sessions_planned',
        'sessions_completed',
        'short_term_goal',
        'long_term_goal',
        'status',
    ];

    protected $casts = [
        'total_sessions_planned' => 'integer',
        'sessions_completed' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DISCHARGED = 'discharged';
    const STATUS_DNF = 'dnf';  // Did Not Finish

    protected static function booted(): void
    {
        static::creating(function (PhysioTreatmentPlan $plan) {
            Log::info('Creating physio treatment plan', [
                'patient_id' => $plan->patient_id,
                'diagnosis' => $plan->diagnosis,
                'total_sessions_planned' => $plan->total_sessions_planned
            ]);
        });

        static::updating(function (PhysioTreatmentPlan $plan) {
            Log::info('Updating physio treatment plan', [
                'id' => $plan->id,
                'changes' => $plan->getDirty()
            ]);
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function initialVisit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    public function hepPlans(): HasMany
    {
        return $this->hasMany(PhysioHep::class, 'patient_id', 'patient_id');
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getProgressPercentage(): float
    {
        if (!$this->total_sessions_planned || $this->total_sessions_planned == 0) {
            return 0;
        }
        return min(100, ($this->sessions_completed / $this->total_sessions_planned) * 100);
    }

    public function incrementSessionsCompleted(): void
    {
        $this->increment('sessions_completed');
        
        Log::info('Physio session completed', [
            'plan_id' => $this->id,
            'sessions_completed' => $this->sessions_completed,
            'total_planned' => $this->total_sessions_planned
        ]);

        // Auto-complete if all sessions done
        if ($this->sessions_completed >= $this->total_sessions_planned) {
            $this->update(['status' => self::STATUS_COMPLETED]);
            Log::info('Physio treatment plan completed', ['plan_id' => $this->id]);
        }
    }
}
