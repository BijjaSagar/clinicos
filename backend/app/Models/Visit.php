<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

class Visit extends Model
{
    protected $table = 'visits';

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'specialty',
        'visit_number',
        'status',
        'chief_complaint',
        'history',
        'structured_data',
        'diagnosis_code',
        'diagnosis_text',
        'plan',
        'followup_in_days',
        'followup_date',
        'ai_dictation_raw',
        'ai_summary',
        'fhir_bundle',
        'fhir_resource_id',
        'abdm_care_context_id',
        'abdm_pushed_at',
        'started_at',
        'finalised_at',
    ];

    protected $casts = [
        'structured_data' => 'array',
        'visit_number' => 'integer',
        'followup_in_days' => 'integer',
        'followup_date' => 'date',
        'abdm_pushed_at' => 'datetime',
        'started_at' => 'datetime',
        'finalised_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_FINALISED = 'finalised';

    protected static function booted(): void
    {
        static::creating(function (Visit $visit) {
            Log::info('Creating visit', [
                'clinic_id' => $visit->clinic_id,
                'patient_id' => $visit->patient_id,
                'doctor_id' => $visit->doctor_id,
                'specialty' => $visit->specialty
            ]);

            // Auto-increment visit_number for patient
            if (!$visit->visit_number) {
                $lastVisit = Visit::where('patient_id', $visit->patient_id)->max('visit_number');
                $visit->visit_number = ($lastVisit ?? 0) + 1;
            }
        });

        static::created(function (Visit $visit) {
            Log::info('Visit created', ['id' => $visit->id, 'visit_number' => $visit->visit_number]);
        });

        static::updating(function (Visit $visit) {
            Log::info('Updating visit', ['id' => $visit->id, 'changes' => $visit->getDirty()]);
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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function lesions(): HasMany
    {
        return $this->hasMany(VisitLesion::class);
    }

    public function scales(): HasMany
    {
        return $this->hasMany(VisitScale::class);
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(VisitProcedure::class);
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PatientPhoto::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function physioHep(): HasMany
    {
        return $this->hasMany(PhysioHep::class);
    }

    public function ophthalVaLog(): HasOne
    {
        return $this->hasOne(OphthalVaLog::class);
    }

    public function ophthalRefraction(): HasOne
    {
        return $this->hasOne(OphthalRefraction::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeFinalised($query)
    {
        return $query->where('status', self::STATUS_FINALISED);
    }

    public function scopeBySpecialty($query, string $specialty)
    {
        return $query->where('specialty', $specialty);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isFinalised(): bool
    {
        return $this->status === self::STATUS_FINALISED;
    }

    public function canFinalise(): bool
    {
        return $this->isDraft() && !empty($this->diagnosis_text);
    }

    public function finalise(): void
    {
        $this->update([
            'status' => self::STATUS_FINALISED,
            'finalised_at' => now(),
        ]);
        Log::info('Visit finalised', ['id' => $this->id]);
    }

    public function hasAbdmConsent(): bool
    {
        return $this->patient->abdm_consent_active ?? false;
    }

    public function wasPushedToAbdm(): bool
    {
        return !is_null($this->abdm_pushed_at);
    }

    public function getStructuredField(string $key, $default = null)
    {
        return data_get($this->structured_data, $key, $default);
    }

    public function setStructuredField(string $key, $value): void
    {
        $data = $this->structured_data ?? [];
        data_set($data, $key, $value);
        $this->structured_data = $data;
    }
}
