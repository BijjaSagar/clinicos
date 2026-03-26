<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class LabOrderTest extends Model
{
    protected $table = 'lab_order_tests';

    public $timestamps = false;

    protected $fillable = [
        'lab_order_id',
        'test_catalog_id',
        'test_code',
        'test_name',
        'is_urgent',
        'unit_price',
        'result_value',
        'result_unit',
        'reference_range',
        'is_abnormal',
    ];

    protected $casts = [
        'is_urgent' => 'boolean',
        'unit_price' => 'decimal:2',
        'is_abnormal' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (LabOrderTest $test) {
            Log::info('Adding test to lab order', [
                'lab_order_id' => $test->lab_order_id,
                'test_name' => $test->test_name
            ]);
        });
    }

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function testCatalog(): BelongsTo
    {
        return $this->belongsTo(LabTestCatalog::class, 'test_catalog_id');
    }

    public function hasResult(): bool
    {
        return !is_null($this->result_value);
    }

    public function isAbnormal(): bool
    {
        return $this->is_abnormal === true;
    }

    public function getResultWithUnit(): string
    {
        if (!$this->result_value) {
            return 'Pending';
        }

        return $this->result_unit
            ? "{$this->result_value} {$this->result_unit}"
            : $this->result_value;
    }
}
