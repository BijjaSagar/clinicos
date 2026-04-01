<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdVital extends Model
{
    protected $table = 'ipd_vitals';

    protected $guarded = [];

    protected $casts = [
        'recorded_at'       => 'datetime',
        'temperature'       => 'float',
        'pulse'             => 'integer',
        'bp_systolic'       => 'integer',
        'bp_diastolic'      => 'integer',
        'respiratory_rate'  => 'integer',
        'spo2'              => 'float',
        'pain_score'        => 'integer',
        'gcs'               => 'integer',
        'weight'            => 'float',
        'height'            => 'float',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_admission_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isTempAbnormal(): bool
    {
        return $this->temperature !== null && ($this->temperature < 36.0 || $this->temperature > 37.5);
    }

    public function isPulseAbnormal(): bool
    {
        return $this->pulse !== null && ($this->pulse < 60 || $this->pulse > 100);
    }

    public function isBpAbnormal(): bool
    {
        return ($this->bp_systolic !== null && ($this->bp_systolic < 90 || $this->bp_systolic > 140))
            || ($this->bp_diastolic !== null && ($this->bp_diastolic < 60 || $this->bp_diastolic > 90));
    }

    public function isSpo2Abnormal(): bool
    {
        return $this->spo2 !== null && $this->spo2 < 95;
    }
}
