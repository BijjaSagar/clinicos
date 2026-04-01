<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Clinic extends Model
{
    use SoftDeletes;

    protected $table = 'clinics';

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'facility_type',
        'licensed_beds',
        'hims_features',
        'specialties',
        'owner_user_id',
        'gstin',
        'pan',
        'registration_number',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'pincode',
        'phone',
        'email',
        'logo_url',
        'hfr_id',
        'hfr_facility_id',
        'hfr_status',
        'abdm_m1_live',
        'abdm_m2_live',
        'abdm_m3_live',
        'razorpay_account_id',
        'whatsapp_phone_number_id',
        'whatsapp_waba_id',
        'gsp_client_id',
        'settings',
        'is_active',
        'trial_ends_at',
    ];

    protected $casts = [
        'specialties' => 'array',
        'hims_features' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'abdm_m1_live' => 'boolean',
        'abdm_m2_live' => 'boolean',
        'abdm_m3_live' => 'boolean',
        'trial_ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Clinic $clinic) {
            Log::info('Creating new clinic', ['name' => $clinic->name, 'slug' => $clinic->slug]);
        });

        static::created(function (Clinic $clinic) {
            Log::info('Clinic created successfully', ['id' => $clinic->id, 'name' => $clinic->name]);
        });

        static::updating(function (Clinic $clinic) {
            Log::info('Updating clinic', ['id' => $clinic->id, 'changes' => $clinic->getDirty()]);
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        Log::debug('Accessing clinic owner relationship', ['clinic_id' => $this->id]);
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ClinicLocation::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(ClinicRoom::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(ClinicEquipment::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function appointmentServices(): HasMany
    {
        return $this->hasMany(AppointmentService::class);
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPlan($query, string $plan)
    {
        return $query->where('plan', $plan);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isAbdmM1Live(): bool
    {
        return $this->abdm_m1_live === true;
    }

    public function isAbdmM2Live(): bool
    {
        return $this->abdm_m2_live === true;
    }

    public function hasSpecialty(string $specialty): bool
    {
        return in_array($specialty, $this->specialties ?? []);
    }

    public function isTrialActive(): bool
    {
        if (!$this->trial_ends_at) {
            return false;
        }
        return $this->trial_ends_at->isFuture();
    }

    /**
     * Hospital-capable facility types (HIMS expansion).
     */
    public function isHospitalFacility(): bool
    {
        $t = $this->facility_type ?? 'clinic';
        Log::debug('Clinic::isHospitalFacility', ['clinic_id' => $this->id, 'facility_type' => $t]);

        return in_array($t, ['hospital', 'multispecialty_hospital'], true);
    }

    /**
     * Whether a planned HIMS feature is enabled for this tenant (see config/hims_expansion.php).
     */
    public function hasHimsFeature(string $feature): bool
    {
        $flags = $this->hims_features ?? [];
        $enabled = !empty($flags[$feature]);
        Log::debug('Clinic::hasHimsFeature', [
            'clinic_id' => $this->id,
            'feature'   => $feature,
            'enabled'   => $enabled,
        ]);

        return $enabled;
    }

    /**
     * All known HIMS keys false — for super admin seeding when enabling hospital tier.
     *
     * @return array<string, bool>
     */
    public static function defaultHimsFeatureMap(): array
    {
        $keys = array_keys(config('hims_expansion.hims_feature_keys', []));

        return array_fill_keys($keys, false);
    }
}
