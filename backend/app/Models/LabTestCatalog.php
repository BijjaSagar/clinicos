<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class LabTestCatalog extends Model
{
    protected $table = 'lab_tests_catalog';

    public $timestamps = true;

    protected $fillable = [
        'clinic_id',
        'name',
        'code',
        'category',
        'sample_type',
        'method',
        'turnaround_hours',
        'price',
        'reference_range_male',
        'reference_range_female',
        'reference_range_pediatric',
        'unit',
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
                'clinic_id' => $test->clinic_id,
                'name' => $test->name
            ]);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearchByName($query, string $search)
    {
        return $query->where('name', 'like', "%{$search}%");
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
