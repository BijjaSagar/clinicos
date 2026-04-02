<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'patients';

    protected $fillable = [
        'clinic_id',
        'name',
        'dob',
        'age_years',
        'sex',
        'blood_group',
        'phone',
        'phone_alt',
        'email',
        'address',
        'abha_id',
        'abha_address',
        'abha_verified',
        'abdm_consent_active',
        'known_allergies',
        'chronic_conditions',
        'current_medications',
        'family_history',
        'referred_by',
        'source',
        'visit_count',
        'last_visit_date',
        'next_followup_date',
        'photo_consent_given',
        'photo_consent_at',
        'photo_consent_signature_path',
    ];

    protected $casts = [
        'dob' => 'date',
        'known_allergies' => 'array',
        'chronic_conditions' => 'array',
        'current_medications' => 'array',
        'family_history' => 'array',
        'abha_verified' => 'boolean',
        'abdm_consent_active' => 'boolean',
        'photo_consent_given' => 'boolean',
        'photo_consent_at' => 'datetime',
        'last_visit_date' => 'date',
        'next_followup_date' => 'date',
        'visit_count' => 'integer',
        'age_years' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Patient $patient) {
            Log::info('Creating new patient', [
                'clinic_id' => $patient->clinic_id,
                'name' => $patient->name,
                'phone' => $patient->phone
            ]);
        });

        static::created(function (Patient $patient) {
            Log::info('Patient created successfully', ['id' => $patient->id, 'name' => $patient->name]);
        });

        static::updating(function (Patient $patient) {
            Log::info('Updating patient', ['id' => $patient->id, 'changes' => $patient->getDirty()]);
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(PatientFamilyMember::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PatientPhoto::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function dentalTeeth(): HasMany
    {
        return $this->hasMany(DentalTooth::class);
    }

    public function physioTreatmentPlans(): HasMany
    {
        return $this->hasMany(PhysioTreatmentPlan::class);
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
    }

    public function abdmConsents(): HasMany
    {
        return $this->hasMany(AbdmConsent::class);
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function scopeWithAbha($query)
    {
        return $query->whereNotNull('abha_id');
    }

    public function scopeSearchByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeSearchByPhone($query, string $phone)
    {
        return $query->where('phone', 'like', "%{$phone}%");
    }

    public function scopeNeedingFollowup($query)
    {
        return $query->whereNotNull('next_followup_date')
                     ->where('next_followup_date', '<=', now()->addDays(7));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function getAge(): ?int
    {
        if ($this->dob) {
            return $this->dob->age;
        }
        return $this->age_years;
    }

    public function hasAbha(): bool
    {
        return !empty($this->abha_id);
    }

    public function hasPhotoConsent(): bool
    {
        return $this->photo_consent_given === true;
    }

    public function getAllergiesString(): string
    {
        return implode(', ', $this->known_allergies ?? []);
    }

    public function getConditionsString(): string
    {
        return implode(', ', $this->chronic_conditions ?? []);
    }

    public function incrementVisitCount(): void
    {
        $this->increment('visit_count');
        $this->update(['last_visit_date' => now()->toDateString()]);
        Log::info('Patient visit count incremented', ['patient_id' => $this->id, 'new_count' => $this->visit_count]);
    }
}
