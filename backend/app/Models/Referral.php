<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Referral extends Model
{
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'visit_id',
        'from_doctor_id',
        'to_specialty',
        'to_facility_name',
        'to_doctor_name',
        'urgency',
        'reason',
        'clinical_summary',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Referral $r) {
            Log::info('Referral creating', ['clinic_id' => $r->clinic_id, 'patient_id' => $r->patient_id]);
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

    public function fromDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_doctor_id');
    }
}
