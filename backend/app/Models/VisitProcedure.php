<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class VisitProcedure extends Model
{
    protected $table = 'visit_procedures';

    public $timestamps = false;

    protected $fillable = [
        'visit_id',
        'clinic_id',
        'procedure_code',
        'procedure_name',
        'specialty',
        'parameters',
        'body_region',
        'notes',
    ];

    protected $casts = [
        'parameters' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (VisitProcedure $procedure) {
            Log::info('Creating visit procedure', [
                'visit_id' => $procedure->visit_id,
                'procedure_name' => $procedure->procedure_name,
                'specialty' => $procedure->specialty
            ]);
        });
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function scopeBySpecialty($query, string $specialty)
    {
        return $query->where('specialty', $specialty);
    }

    public function getParameter(string $key, $default = null)
    {
        return data_get($this->parameters, $key, $default);
    }

    // Dermatology procedure parameters example:
    // {agent, concentration, areas, duration_mins, sessions_total, session_number}
    // Laser: {type, wavelength_nm, fluence, pulse_duration, areas}
    // Physio: {modality, settings, duration_mins, exercises_count}
}
