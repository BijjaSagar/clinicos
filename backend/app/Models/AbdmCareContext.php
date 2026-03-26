<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class AbdmCareContext extends Model
{
    protected $table = 'abdm_care_contexts';

    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'clinic_id',
        'visit_id',
        'care_context_reference',
        'display_name',
        'hi_type',
        'fhir_resource_type',
        'fhir_bundle_url',
        'pushed_at',
        'status',
    ];

    protected $casts = [
        'pushed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REVOKED = 'revoked';

    const HI_TYPE_OP_CONSULTATION = 'OPConsultation';
    const HI_TYPE_PRESCRIPTION = 'Prescription';
    const HI_TYPE_DIAGNOSTIC_REPORT = 'DiagnosticReport';
    const HI_TYPE_DISCHARGE_SUMMARY = 'DischargeSummary';

    protected static function booted(): void
    {
        static::creating(function (AbdmCareContext $context) {
            Log::info('Creating ABDM care context', [
                'patient_id' => $context->patient_id,
                'visit_id' => $context->visit_id,
                'hi_type' => $context->hi_type
            ]);

            // Auto-generate care context reference if not set
            if (empty($context->care_context_reference) && $context->visit_id) {
                $context->care_context_reference = "CC-{$context->visit_id}";
            }
        });

        static::created(function (AbdmCareContext $context) {
            Log::info('ABDM care context created', [
                'id' => $context->id,
                'care_context_reference' => $context->care_context_reference
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

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByHiType($query, string $hiType)
    {
        return $query->where('hi_type', $hiType);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function wasPushed(): bool
    {
        return !is_null($this->pushed_at);
    }

    public function markAsPushed(): void
    {
        $this->update(['pushed_at' => now()]);
        Log::info('Care context marked as pushed', ['id' => $this->id]);
    }

    public function revoke(): void
    {
        $this->update(['status' => self::STATUS_REVOKED]);
        Log::info('Care context revoked', ['id' => $this->id]);
    }
}
