<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class AbdmHiuLink extends Model
{
    protected $table = 'abdm_hiu_links';

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'hip_id',
        'care_context_reference',
        'status',
        'gateway_payload',
    ];

    protected $casts = [
        'gateway_payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (AbdmHiuLink $row) {
            Log::info('AbdmHiuLink creating', ['patient_id' => $row->patient_id, 'hip_id' => $row->hip_id]);
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
