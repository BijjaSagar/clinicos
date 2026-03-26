<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class DentalTooth extends Model
{
    protected $table = 'dental_teeth';

    protected $fillable = [
        'patient_id',
        'clinic_id',
        'tooth_code',
        'status',
        'caries',
        'caries_sites',
        'restoration',
        'mobility_grade',
        'recession_mm',
        'bop',
        'pocketing_mm',
        'furcation',
        'notes',
        'last_updated_by',
    ];

    protected $casts = [
        'caries_sites' => 'array',
        'pocketing_mm' => 'array',
        'mobility_grade' => 'integer',
        'recession_mm' => 'decimal:1',
        'furcation' => 'integer',
        'bop' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_PRESENT = 'present';
    const STATUS_MISSING = 'missing';
    const STATUS_EXTRACTED = 'extracted';
    const STATUS_UNERUPTED = 'unerupted';
    const STATUS_IMPACTED = 'impacted';
    const STATUS_IMPLANT = 'implant';

    const CARIES_NONE = 'none';
    const CARIES_INITIAL = 'initial';
    const CARIES_MODERATE = 'moderate';
    const CARIES_ADVANCED = 'advanced';

    const RESTORATION_NONE = 'none';
    const RESTORATION_AMALGAM = 'amalgam';
    const RESTORATION_COMPOSITE = 'composite';
    const RESTORATION_CROWN = 'crown';
    const RESTORATION_BRIDGE = 'bridge';
    const RESTORATION_RCT = 'rct';
    const RESTORATION_VENEER = 'veneer';
    const RESTORATION_IMPLANT_CROWN = 'implant_crown';

    // FDI tooth codes
    const PERMANENT_UPPER_RIGHT = ['18', '17', '16', '15', '14', '13', '12', '11'];
    const PERMANENT_UPPER_LEFT = ['21', '22', '23', '24', '25', '26', '27', '28'];
    const PERMANENT_LOWER_LEFT = ['31', '32', '33', '34', '35', '36', '37', '38'];
    const PERMANENT_LOWER_RIGHT = ['41', '42', '43', '44', '45', '46', '47', '48'];

    protected static function booted(): void
    {
        static::creating(function (DentalTooth $tooth) {
            Log::info('Creating dental tooth record', [
                'patient_id' => $tooth->patient_id,
                'tooth_code' => $tooth->tooth_code
            ]);
        });

        static::updating(function (DentalTooth $tooth) {
            Log::info('Updating dental tooth', [
                'patient_id' => $tooth->patient_id,
                'tooth_code' => $tooth->tooth_code,
                'changes' => $tooth->getDirty()
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

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(DentalToothHistory::class, 'patient_id', 'patient_id')
                    ->where('tooth_code', $this->tooth_code);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopePresent($query)
    {
        return $query->where('status', self::STATUS_PRESENT);
    }

    public function scopeWithCaries($query)
    {
        return $query->where('caries', '!=', self::CARIES_NONE);
    }

    public function isPresent(): bool
    {
        return $this->status === self::STATUS_PRESENT;
    }

    public function hasCaries(): bool
    {
        return $this->caries !== self::CARIES_NONE;
    }

    public function hasRestoration(): bool
    {
        return $this->restoration !== self::RESTORATION_NONE;
    }

    public function isPrimaryTooth(): bool
    {
        $code = (int) $this->tooth_code;
        return $code >= 51 && $code <= 85;
    }

    public function getQuadrant(): int
    {
        $firstDigit = (int) substr($this->tooth_code, 0, 1);
        return $firstDigit;
    }

    public function getToothNumber(): int
    {
        return (int) substr($this->tooth_code, 1, 1);
    }

    public static function initializeChartForPatient(int $patientId, int $clinicId): void
    {
        Log::info('Initializing dental chart for patient', ['patient_id' => $patientId]);

        $allTeeth = array_merge(
            self::PERMANENT_UPPER_RIGHT,
            self::PERMANENT_UPPER_LEFT,
            self::PERMANENT_LOWER_LEFT,
            self::PERMANENT_LOWER_RIGHT
        );

        foreach ($allTeeth as $toothCode) {
            self::firstOrCreate(
                ['patient_id' => $patientId, 'tooth_code' => $toothCode],
                ['clinic_id' => $clinicId, 'status' => self::STATUS_PRESENT, 'caries' => self::CARIES_NONE]
            );
        }
    }
}
