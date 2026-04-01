<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class PrescriptionItem extends Model
{
    protected $table = 'prescription_items';
    
    protected $fillable = [
        'visit_id',
        'drug_id',
        'drug_name',
        'dosage',
        'frequency',
        'duration',
        'route',
        'instructions',
        'quantity',
        'is_substitutable',
        'sort_order',
    ];

    protected $casts = [
        'is_substitutable' => 'boolean',
        'quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($item) {
            Log::info('PrescriptionItem: Creating new item for visit: ' . $item->visit_id . ', drug: ' . $item->drug_name);
        });
    }

    /**
     * Get the visit this prescription item belongs to
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * Get the drug from the database (if linked)
     */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(IndianDrug::class, 'drug_id');
    }

    /**
     * Get frequency label
     */
    public function getFrequencyLabelAttribute(): string
    {
        $labels = [
            'OD' => 'Once daily',
            'BD' => 'Twice daily',
            'TDS' => 'Three times daily',
            'QID' => 'Four times daily',
            'HS' => 'At bedtime',
            'SOS' => 'When needed',
            'STAT' => 'Immediately',
            'AC' => 'Before meals',
            'PC' => 'After meals',
            'Q4H' => 'Every 4 hours',
            'Q6H' => 'Every 6 hours',
            'Q8H' => 'Every 8 hours',
            'Q12H' => 'Every 12 hours',
            'WEEKLY' => 'Once weekly',
            'BIWEEKLY' => 'Twice weekly',
        ];
        
        return $labels[$this->frequency] ?? $this->frequency;
    }

    /**
     * Get formatted prescription line
     */
    public function getFormattedLineAttribute(): string
    {
        $parts = [
            $this->drug_name,
            $this->dosage,
            $this->frequency_label,
            'for ' . $this->duration,
        ];
        
        if ($this->instructions) {
            $parts[] = '(' . $this->instructions . ')';
        }
        
        return implode(' - ', $parts);
    }
}
