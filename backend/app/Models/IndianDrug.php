<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;

class IndianDrug extends Model
{
    protected $table = 'indian_drugs';
    
    protected $fillable = [
        'generic_name',
        'brand_names',
        'drug_class',
        'form',
        'strength',
        'manufacturer',
        'schedule',
        'common_dosages',
        'contraindications',
        'interactions',
        'side_effects',
        'is_controlled',
        'is_active',
    ];

    protected $casts = [
        'brand_names' => 'array',
        'common_dosages' => 'array',
        'contraindications' => 'array',
        'interactions' => 'array',
        'side_effects' => 'array',
        'is_controlled' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get all interactions where this drug is drug_a
     */
    public function interactionsAsA(): BelongsToMany
    {
        return $this->belongsToMany(
            IndianDrug::class,
            'drug_interactions',
            'drug_a_id',
            'drug_b_id'
        )->withPivot(['severity', 'description', 'management']);
    }

    /**
     * Get all interactions where this drug is drug_b
     */
    public function interactionsAsB(): BelongsToMany
    {
        return $this->belongsToMany(
            IndianDrug::class,
            'drug_interactions',
            'drug_b_id',
            'drug_a_id'
        )->withPivot(['severity', 'description', 'management']);
    }

    /**
     * Get all drug interactions
     */
    public function getAllInteractions(): \Illuminate\Support\Collection
    {
        Log::info('IndianDrug: Getting all interactions for drug ID: ' . $this->id);
        return $this->interactionsAsA->merge($this->interactionsAsB);
    }

    /**
     * Check interactions with a list of drug IDs
     */
    public function checkInteractions(array $drugIds): \Illuminate\Support\Collection
    {
        Log::info('IndianDrug: Checking interactions for drug ID: ' . $this->id . ' with drugs: ' . implode(',', $drugIds));
        
        $interactions = \Illuminate\Support\Facades\DB::table('drug_interactions')
            ->where(function ($query) use ($drugIds) {
                $query->where('drug_a_id', $this->id)
                    ->whereIn('drug_b_id', $drugIds);
            })
            ->orWhere(function ($query) use ($drugIds) {
                $query->where('drug_b_id', $this->id)
                    ->whereIn('drug_a_id', $drugIds);
            })
            ->get();
        
        Log::info('IndianDrug: Found ' . $interactions->count() . ' interactions');
        return $interactions;
    }

    /**
     * Search drugs by name (generic or brand)
     */
    public static function searchByName(string $query): \Illuminate\Database\Eloquent\Collection
    {
        Log::info('IndianDrug: Searching for drug: ' . $query);
        
        return self::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('generic_name', 'LIKE', "%{$query}%")
                    ->orWhereRaw("JSON_SEARCH(brand_names, 'one', ?) IS NOT NULL", ["%{$query}%"]);
            })
            ->orderBy('generic_name')
            ->limit(20)
            ->get();
    }

    /**
     * Get display name with strength
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->generic_name} ({$this->strength}) - {$this->form}";
    }

    /**
     * Get first brand name
     */
    public function getFirstBrandAttribute(): ?string
    {
        return $this->brand_names[0] ?? null;
    }
}
