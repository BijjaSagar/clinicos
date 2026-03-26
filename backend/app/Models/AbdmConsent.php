<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class AbdmConsent extends Model
{
    protected $table = 'abdm_consents';

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'consent_request_id',
        'status',
        'purpose',
        'hi_types',
        'date_from',
        'date_to',
        'consent_artefact',
        'consent_artefact_id',
        'granted_at',
        'expires_at',
    ];

    protected $casts = [
        'hi_types' => 'array',
        'consent_artefact' => 'array',
        'date_from' => 'date',
        'date_to' => 'date',
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_REQUESTED = 'REQUESTED';
    const STATUS_GRANTED = 'GRANTED';
    const STATUS_DENIED = 'DENIED';
    const STATUS_REVOKED = 'REVOKED';
    const STATUS_EXPIRED = 'EXPIRED';

    const PURPOSE_CAREMGT = 'CAREMGT';      // Care Management
    const PURPOSE_BTG = 'BTG';               // Break The Glass (emergency)
    const PURPOSE_PATRQT = 'PATRQT';         // Patient Requested
    const PURPOSE_PUBHLTH = 'PUBHLTH';       // Public Health

    protected static function booted(): void
    {
        static::creating(function (AbdmConsent $consent) {
            Log::info('Creating ABDM consent request', [
                'clinic_id' => $consent->clinic_id,
                'patient_id' => $consent->patient_id,
                'purpose' => $consent->purpose,
                'hi_types' => $consent->hi_types
            ]);
        });

        static::created(function (AbdmConsent $consent) {
            Log::info('ABDM consent request created', [
                'id' => $consent->id,
                'consent_request_id' => $consent->consent_request_id
            ]);
        });

        static::updating(function (AbdmConsent $consent) {
            if ($consent->isDirty('status')) {
                Log::info('ABDM consent status changed', [
                    'id' => $consent->id,
                    'old_status' => $consent->getOriginal('status'),
                    'new_status' => $consent->status
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

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeGranted($query)
    {
        return $query->where('status', self::STATUS_GRANTED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_GRANTED)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    public function isGranted(): bool
    {
        return $this->status === self::STATUS_GRANTED;
    }

    public function isActive(): bool
    {
        if (!$this->isGranted()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function markAsGranted(string $artefactId, array $artefact): void
    {
        $this->update([
            'status' => self::STATUS_GRANTED,
            'consent_artefact_id' => $artefactId,
            'consent_artefact' => $artefact,
            'granted_at' => now(),
        ]);
        
        Log::info('ABDM consent granted', ['id' => $this->id, 'artefact_id' => $artefactId]);
    }

    public function markAsRevoked(): void
    {
        $this->update(['status' => self::STATUS_REVOKED]);
        Log::info('ABDM consent revoked', ['id' => $this->id]);
    }
}
