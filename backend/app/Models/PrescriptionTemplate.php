<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class PrescriptionTemplate extends Model
{
    protected $table = 'prescription_templates';
    
    protected $fillable = [
        'clinic_id',
        'created_by',
        'name',
        'diagnosis',
        'specialty',
        'medications',
        'instructions',
        'is_active',
    ];

    protected $casts = [
        'medications' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($template) {
            Log::info('PrescriptionTemplate: Creating new template: ' . $template->name . ' for clinic: ' . $template->clinic_id);
        });
    }

    /**
     * Get the clinic this template belongs to
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to templates for a specific specialty
     */
    public function scopeForSpecialty($query, string $specialty)
    {
        return $query->where(function ($q) use ($specialty) {
            $q->where('specialty', $specialty)
                ->orWhereNull('specialty');
        });
    }

    /**
     * Get medication count
     */
    public function getMedicationCountAttribute(): int
    {
        return count($this->medications ?? []);
    }
}
