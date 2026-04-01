<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class WearableReading extends Model
{
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'device_type',
        'source',
        'recorded_at',
        'systolic',
        'diastolic',
        'heart_rate',
        'glucose_mg_dl',
        'raw',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'raw' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (WearableReading $w) {
            Log::info('WearableReading creating', [
                'patient_id' => $w->patient_id,
                'device_type' => $w->device_type,
            ]);
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
}
