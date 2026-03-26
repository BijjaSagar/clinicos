<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class LabTestCatalog extends Model
{
    protected $table = 'lab_test_catalog';

    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'test_code',
        'test_name',
        'department',
        'sample_type',
        'turnaround_hours',
        'price',
        'is_active',
    ];

    protected $casts = [
        'turnaround_hours' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (LabTestCatalog $test) {
            Log::info('Creating lab test catalog entry', [
                'vendor_id' => $test->vendor_id,
                'test_name' => $test->test_name
            ]);
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorLab::class, 'vendor_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeSearchByName($query, string $search)
    {
        return $query->where('test_name', 'like', "%{$search}%");
    }

    public function getTurnaroundDescription(): string
    {
        if (!$this->turnaround_hours) {
            return 'Unknown';
        }

        if ($this->turnaround_hours < 24) {
            return "{$this->turnaround_hours} hours";
        }

        $days = ceil($this->turnaround_hours / 24);
        return "{$days} day" . ($days > 1 ? 's' : '');
    }
}
