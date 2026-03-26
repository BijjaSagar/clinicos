<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class DentalToothHistory extends Model
{
    protected $table = 'dental_tooth_history';

    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'tooth_code',
        'visit_id',
        'procedure_done',
        'material_used',
        'operator_id',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (DentalToothHistory $history) {
            Log::info('Creating dental tooth history', [
                'patient_id' => $history->patient_id,
                'tooth_code' => $history->tooth_code,
                'procedure_done' => $history->procedure_done
            ]);
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function scopeForTooth($query, string $toothCode)
    {
        return $query->where('tooth_code', $toothCode);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }
}
