<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class ClinicTpaConfig extends Model
{
    protected $table = 'clinic_tpa_configs';

    protected $fillable = [
        'clinic_id',
        'tpa_code',
        'tpa_name',
        'empanelment_id',
        'provider_id',
        'rohini_id',
        'contact_email',
        'contact_phone',
        'portal_url',
        'portal_username',
        'portal_password_encrypted',
        'supported_insurers',
        'is_active',
    ];

    protected $casts = [
        'supported_insurers' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicTpaConfig $row) {
            Log::info('ClinicTpaConfig: creating', [
                'clinic_id' => $row->clinic_id,
                'tpa_code' => $row->tpa_code,
            ]);
        });

        static::updated(function (ClinicTpaConfig $row) {
            Log::info('ClinicTpaConfig: updated', ['id' => $row->id, 'clinic_id' => $row->clinic_id]);
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
