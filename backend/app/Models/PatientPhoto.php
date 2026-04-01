<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class PatientPhoto extends Model
{
    use SoftDeletes;

    protected $table = 'patient_photos';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'visit_id',
        's3_key',
        's3_bucket',
        'file_path',
        'file_name',
        'storage_disk',
        'description',
        'body_subregion',
        'pair_id',
        'file_size_kb',
        'mime_type',
        'body_region',
        'view_angle',
        'condition_tag',
        'procedure_tag',
        'photo_type',
        'consent_obtained',
        'consent_at',
        'is_encrypted',
        'can_use_for_marketing',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size_kb' => 'integer',
        'consent_obtained' => 'boolean',
        'consent_at' => 'datetime',
        'is_encrypted' => 'boolean',
        'can_use_for_marketing' => 'boolean',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const TYPE_BEFORE = 'before';
    const TYPE_AFTER = 'after';
    const TYPE_PROGRESS = 'progress';
    const TYPE_CLINICAL = 'clinical';

    protected static function booted(): void
    {
        static::creating(function (PatientPhoto $photo) {
            Log::info('Creating patient photo', [
                'patient_id' => $photo->patient_id,
                'visit_id' => $photo->visit_id,
                'body_region' => $photo->body_region,
                'photo_type' => $photo->photo_type
            ]);
        });

        static::created(function (PatientPhoto $photo) {
            Log::info('Patient photo created', ['id' => $photo->id]);
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

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeForRegion($query, string $region)
    {
        return $query->where('body_region', $region);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('photo_type', $type);
    }

    public function scopeWithConsent($query)
    {
        return $query->where('consent_obtained', true);
    }

    public function scopeForVisit($query, int $visitId)
    {
        return $query->where('visit_id', $visitId);
    }

    public function hasConsent(): bool
    {
        return $this->consent_obtained === true;
    }

    public function getS3Url(): string
    {
        return "https://{$this->s3_bucket}.s3.amazonaws.com/{$this->s3_key}";
    }

    public function isBefore(): bool
    {
        return $this->photo_type === self::TYPE_BEFORE;
    }

    public function isAfter(): bool
    {
        return $this->photo_type === self::TYPE_AFTER;
    }
}
