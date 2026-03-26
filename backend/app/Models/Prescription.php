<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Prescription extends Model
{
    protected $table = 'prescriptions';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'visit_id',
        'patient_id',
        'doctor_id',
        'hpr_signed_ref',
        'fhir_resource_id',
        'pdf_url',
        'whatsapp_sent_at',
        'whatsapp_message_id',
        'valid_days',
    ];

    protected $casts = [
        'valid_days' => 'integer',
        'whatsapp_sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Prescription $prescription) {
            Log::info('Creating prescription', [
                'visit_id' => $prescription->visit_id,
                'patient_id' => $prescription->patient_id,
                'doctor_id' => $prescription->doctor_id
            ]);
        });

        static::created(function (Prescription $prescription) {
            Log::info('Prescription created', ['id' => $prescription->id]);
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function drugs(): HasMany
    {
        return $this->hasMany(PrescriptionDrug::class)->orderBy('sort_order');
    }

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function isValid(): bool
    {
        $expiryDate = $this->created_at->addDays($this->valid_days);
        return now()->lt($expiryDate);
    }

    public function wasSentViaWhatsapp(): bool
    {
        return !is_null($this->whatsapp_sent_at);
    }

    public function isHprSigned(): bool
    {
        return !empty($this->hpr_signed_ref);
    }
}
