<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class LabTestCatalog extends Model
{
    protected $table = 'lab_tests_catalog';

    public $timestamps = true;

    // Actual DB columns
    protected $fillable = [
        'clinic_id',
        'department_id',
        'test_name',
        'test_code',
        'test_type',
        'price',
        'sample_type',
        'unit',
        'tat_hours',
        'is_active',
    ];

    protected $casts = [
        'tat_hours'           => 'integer',
        'price'               => 'decimal:2',
        'is_active'           => 'boolean',
        'normal_range_male'   => 'array',
        'normal_range_female' => 'array',
        'normal_range_child'  => 'array',
    ];

    // ─── Compatibility Accessors (so views using ->name, ->code etc. still work) ──

    public function getNameAttribute(): string
    {
        return $this->test_name ?? '';
    }

    public function getCodeAttribute(): ?string
    {
        return $this->test_code;
    }

    public function getCategoryAttribute(): ?string
    {
        return $this->department?->name;
    }

    public function getTurnaroundHoursAttribute(): ?int
    {
        return $this->tat_hours;
    }

    public function getReferenceRangeAttribute(): ?string
    {
        $male = $this->normal_range_male;
        if (is_array($male) && isset($male['range'])) {
            return $male['range'];
        }
        return null;
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LabDepartment::class, 'department_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function getTurnaroundDescription(): string
    {
        if (!$this->tat_hours) return 'Unknown';
        if ($this->tat_hours < 24) return "{$this->tat_hours} hours";
        $days = ceil($this->tat_hours / 24);
        return "{$days} day" . ($days > 1 ? 's' : '');
    }

    protected static function booted(): void
    {
        static::creating(function (LabTestCatalog $test) {
            Log::info('Creating lab test catalog entry', [
                'clinic_id' => $test->clinic_id,
                'test_name' => $test->test_name,
            ]);
        });
    }
}
