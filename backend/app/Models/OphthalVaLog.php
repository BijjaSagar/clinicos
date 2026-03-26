<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class OphthalVaLog extends Model
{
    protected $table = 'ophthal_va_logs';

    public $timestamps = false;

    protected $fillable = [
        'visit_id',
        'patient_id',
        'va_od_unaided',
        'va_os_unaided',
        'va_od_pinhole',
        'va_os_pinhole',
        'va_od_bcva',
        'va_os_bcva',
        'iop_od_mmhg',
        'iop_os_mmhg',
        'iop_method',
        'iop_time',
        'ac_grade_od',
        'cornea_od',
        'lens_od_locs',
        'ac_grade_os',
        'cornea_os',
        'lens_os_locs',
        'cdr_od',
        'cdr_os',
        'fundus_od_notes',
        'fundus_os_notes',
    ];

    protected $casts = [
        'iop_od_mmhg' => 'decimal:1',
        'iop_os_mmhg' => 'decimal:1',
        'cdr_od' => 'decimal:2',
        'cdr_os' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (OphthalVaLog $log) {
            Log::info('Creating ophthal VA log', [
                'visit_id' => $log->visit_id,
                'patient_id' => $log->patient_id,
                'va_od_bcva' => $log->va_od_bcva,
                'va_os_bcva' => $log->va_os_bcva
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

    public function getVaSummary(): string
    {
        return sprintf(
            'VA: OD %s / OS %s (BCVA: OD %s / OS %s)',
            $this->va_od_unaided ?? 'N/A',
            $this->va_os_unaided ?? 'N/A',
            $this->va_od_bcva ?? 'N/A',
            $this->va_os_bcva ?? 'N/A'
        );
    }

    public function getIopSummary(): string
    {
        return sprintf(
            'IOP: OD %s / OS %s mmHg (%s)',
            $this->iop_od_mmhg ?? 'N/A',
            $this->iop_os_mmhg ?? 'N/A',
            $this->iop_method ?? 'method unknown'
        );
    }

    public function hasElevatedIop(): bool
    {
        $threshold = 21; // Normal IOP is typically below 21 mmHg
        return ($this->iop_od_mmhg && $this->iop_od_mmhg > $threshold) ||
               ($this->iop_os_mmhg && $this->iop_os_mmhg > $threshold);
    }
}
