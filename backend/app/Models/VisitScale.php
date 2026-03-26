<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class VisitScale extends Model
{
    protected $table = 'visit_scales';

    public $timestamps = false;

    protected $fillable = [
        'visit_id',
        'scale_name',
        'score',
        'components',
        'interpretation',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'components' => 'array',
        'created_at' => 'datetime',
    ];

    // Dermatology scales
    const SCALE_PASI = 'PASI';      // Psoriasis Area Severity Index
    const SCALE_IGA = 'IGA';        // Investigator Global Assessment
    const SCALE_DLQI = 'DLQI';      // Dermatology Life Quality Index

    // Physiotherapy scales
    const SCALE_VAS = 'VAS';        // Visual Analog Scale (pain)
    const SCALE_ROM = 'ROM';        // Range of Motion
    const SCALE_MMT = 'MMT';        // Manual Muscle Testing
    const SCALE_BARTHEL = 'BARTHEL';
    const SCALE_FIM = 'FIM';        // Functional Independence Measure
    const SCALE_DASH = 'DASH';      // Disabilities of Arm, Shoulder, Hand
    const SCALE_WOMAC = 'WOMAC';    // Western Ontario and McMaster Universities Osteoarthritis Index
    const SCALE_NDI = 'NDI';        // Neck Disability Index

    // ENT scales
    const SCALE_DHI = 'DHI';        // Dizziness Handicap Inventory

    protected static function booted(): void
    {
        static::creating(function (VisitScale $scale) {
            Log::info('Creating visit scale', [
                'visit_id' => $scale->visit_id,
                'scale_name' => $scale->scale_name,
                'score' => $scale->score
            ]);
        });
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function scopeByScaleName($query, string $scaleName)
    {
        return $query->where('scale_name', $scaleName);
    }

    public static function getInterpretation(string $scaleName, float $score): string
    {
        $interpretations = [
            'PASI' => fn($s) => match(true) {
                $s == 0 => 'Clear',
                $s <= 3 => 'Mild',
                $s <= 10 => 'Moderate',
                $s <= 20 => 'Severe',
                default => 'Very Severe'
            },
            'IGA' => fn($s) => match((int)$s) {
                0 => 'Clear',
                1 => 'Almost Clear',
                2 => 'Mild',
                3 => 'Moderate',
                4 => 'Severe',
                default => 'Unknown'
            },
            'DLQI' => fn($s) => match(true) {
                $s <= 1 => 'No effect',
                $s <= 5 => 'Small effect',
                $s <= 10 => 'Moderate effect',
                $s <= 20 => 'Very large effect',
                default => 'Extremely large effect'
            },
            'VAS' => fn($s) => match(true) {
                $s == 0 => 'No pain',
                $s <= 3 => 'Mild pain',
                $s <= 6 => 'Moderate pain',
                $s <= 10 => 'Severe pain',
                default => 'Unknown'
            },
        ];

        if (isset($interpretations[$scaleName])) {
            return $interpretations[$scaleName]($score);
        }

        return 'Score: ' . $score;
    }
}
