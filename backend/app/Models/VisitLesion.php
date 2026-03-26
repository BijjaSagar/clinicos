<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class VisitLesion extends Model
{
    protected $table = 'visit_lesions';

    public $timestamps = false;

    protected $fillable = [
        'visit_id',
        'body_region',
        'view',
        'x_pct',
        'y_pct',
        'lesion_type',
        'size_cm',
        'colour',
        'border',
        'surface',
        'distribution',
        'notes',
    ];

    protected $casts = [
        'x_pct' => 'decimal:2',
        'y_pct' => 'decimal:2',
        'size_cm' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    const VIEW_FRONT = 'front';
    const VIEW_BACK = 'back';
    const VIEW_LEFT = 'left';
    const VIEW_RIGHT = 'right';

    const LESION_TYPES = [
        'macule', 'papule', 'plaque', 'vesicle', 'bulla',
        'pustule', 'nodule', 'patch', 'wheal', 'scale',
        'crust', 'erosion', 'ulcer', 'fissure', 'atrophy'
    ];

    protected static function booted(): void
    {
        static::creating(function (VisitLesion $lesion) {
            Log::info('Creating visit lesion', [
                'visit_id' => $lesion->visit_id,
                'body_region' => $lesion->body_region,
                'lesion_type' => $lesion->lesion_type
            ]);
        });
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function getPositionDescription(): string
    {
        return "{$this->body_region} ({$this->view} view) at ({$this->x_pct}%, {$this->y_pct}%)";
    }

    public function toAnnotationArray(): array
    {
        return [
            'id' => $this->id,
            'region' => $this->body_region,
            'view' => $this->view,
            'x' => $this->x_pct,
            'y' => $this->y_pct,
            'type' => $this->lesion_type,
            'size' => $this->size_cm,
            'colour' => $this->colour,
            'border' => $this->border,
            'surface' => $this->surface,
            'notes' => $this->notes,
        ];
    }
}
