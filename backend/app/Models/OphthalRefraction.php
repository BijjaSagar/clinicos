<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class OphthalRefraction extends Model
{
    protected $table = 'ophthal_refractions';

    public $timestamps = false;

    protected $fillable = [
        'visit_id',
        'patient_id',
        'refraction_type',
        'od_sphere',
        'od_cylinder',
        'od_axis',
        'od_add',
        'od_prism',
        'od_base',
        'os_sphere',
        'os_cylinder',
        'os_axis',
        'os_add',
        'os_prism',
        'os_base',
        'is_final_prescription',
        'pdf_url',
    ];

    protected $casts = [
        'od_sphere' => 'decimal:2',
        'od_cylinder' => 'decimal:2',
        'od_axis' => 'integer',
        'od_add' => 'decimal:2',
        'od_prism' => 'decimal:2',
        'os_sphere' => 'decimal:2',
        'os_cylinder' => 'decimal:2',
        'os_axis' => 'integer',
        'os_add' => 'decimal:2',
        'os_prism' => 'decimal:2',
        'is_final_prescription' => 'boolean',
        'created_at' => 'datetime',
    ];

    const TYPE_SUBJECTIVE = 'subjective';
    const TYPE_CYCLOPLEGIC = 'cycloplegic';
    const TYPE_MANIFEST = 'manifest';
    const TYPE_CONTACT_LENS = 'contact_lens';

    protected static function booted(): void
    {
        static::creating(function (OphthalRefraction $refraction) {
            Log::info('Creating ophthal refraction', [
                'visit_id' => $refraction->visit_id,
                'patient_id' => $refraction->patient_id,
                'refraction_type' => $refraction->refraction_type
            ]);
        });
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeFinalPrescription($query)
    {
        return $query->where('is_final_prescription', true);
    }

    public function isFinalPrescription(): bool
    {
        return $this->is_final_prescription === true;
    }

    public function getOdPrescription(): string
    {
        $parts = [];

        if ($this->od_sphere !== null) {
            $sign = $this->od_sphere >= 0 ? '+' : '';
            $parts[] = $sign . number_format($this->od_sphere, 2) . ' DS';
        }

        if ($this->od_cylinder !== null && $this->od_cylinder != 0) {
            $sign = $this->od_cylinder >= 0 ? '+' : '';
            $parts[] = $sign . number_format($this->od_cylinder, 2) . ' DC x ' . ($this->od_axis ?? 0) . '°';
        }

        if ($this->od_add !== null && $this->od_add != 0) {
            $parts[] = 'Add +' . number_format($this->od_add, 2);
        }

        return implode(' / ', $parts) ?: 'Plano';
    }

    public function getOsPrescription(): string
    {
        $parts = [];

        if ($this->os_sphere !== null) {
            $sign = $this->os_sphere >= 0 ? '+' : '';
            $parts[] = $sign . number_format($this->os_sphere, 2) . ' DS';
        }

        if ($this->os_cylinder !== null && $this->os_cylinder != 0) {
            $sign = $this->os_cylinder >= 0 ? '+' : '';
            $parts[] = $sign . number_format($this->os_cylinder, 2) . ' DC x ' . ($this->os_axis ?? 0) . '°';
        }

        if ($this->os_add !== null && $this->os_add != 0) {
            $parts[] = 'Add +' . number_format($this->os_add, 2);
        }

        return implode(' / ', $parts) ?: 'Plano';
    }

    public function needsReadingGlasses(): bool
    {
        return ($this->od_add && $this->od_add > 0) || ($this->os_add && $this->os_add > 0);
    }
}
