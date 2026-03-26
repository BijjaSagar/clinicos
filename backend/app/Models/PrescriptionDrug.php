<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class PrescriptionDrug extends Model
{
    protected $table = 'prescription_drugs';

    public $timestamps = false;

    protected $fillable = [
        'prescription_id',
        'drug_db_id',
        'drug_name',
        'generic_name',
        'strength',
        'form',
        'dose',
        'frequency',
        'route',
        'duration',
        'instructions',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PrescriptionDrug $drug) {
            Log::info('Adding drug to prescription', [
                'prescription_id' => $drug->prescription_id,
                'drug_name' => $drug->drug_name,
                'dose' => $drug->dose,
                'frequency' => $drug->frequency
            ]);
        });
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function drugDb(): BelongsTo
    {
        return $this->belongsTo(IndianDrug::class, 'drug_db_id');
    }

    public function getFullDosageString(): string
    {
        $parts = array_filter([
            $this->dose,
            $this->frequency,
            $this->duration ? "for {$this->duration}" : null,
        ]);
        return implode(' ', $parts);
    }

    public function getDisplayName(): string
    {
        $parts = array_filter([
            $this->drug_name,
            $this->strength,
            $this->form ? "({$this->form})" : null,
        ]);
        return implode(' ', $parts);
    }
}
