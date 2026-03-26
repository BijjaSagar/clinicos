<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class IndianDrug extends Model
{
    protected $table = 'indian_drugs';

    public $timestamps = false;

    protected $fillable = [
        'generic_name',
        'brand_names',
        'drug_class',
        'form',
        'strength',
        'manufacturer',
        'schedule',
        'interactions',
        'contraindications',
        'common_dosages',
        'is_controlled',
    ];

    protected $casts = [
        'brand_names' => 'array',
        'interactions' => 'array',
        'contraindications' => 'array',
        'common_dosages' => 'array',
        'is_controlled' => 'boolean',
    ];

    public function scopeSearchByName($query, string $search)
    {
        Log::debug('Searching drugs', ['search' => $search]);
        
        return $query->where(function ($q) use ($search) {
            $q->where('generic_name', 'like', "%{$search}%")
              ->orWhereJsonContains('brand_names', $search);
        });
    }

    public function scopeByDrugClass($query, string $class)
    {
        return $query->where('drug_class', $class);
    }

    public function scopeByForm($query, string $form)
    {
        return $query->where('form', $form);
    }

    public function scopeControlled($query)
    {
        return $query->where('is_controlled', true);
    }

    public function getBrandNamesString(): string
    {
        return implode(', ', $this->brand_names ?? []);
    }

    public function hasInteractionWith(string $drugName): bool
    {
        return in_array(strtolower($drugName), array_map('strtolower', $this->interactions ?? []));
    }

    public function isScheduleH(): bool
    {
        return $this->schedule === 'H';
    }

    public function isScheduleH1(): bool
    {
        return $this->schedule === 'H1';
    }

    public function getScheduleDescription(): string
    {
        $descriptions = [
            'H' => 'Schedule H - Prescription only',
            'H1' => 'Schedule H1 - Prescription with record',
            'G' => 'Schedule G - Caution required',
            'OTC' => 'Over the counter',
        ];

        return $descriptions[$this->schedule] ?? 'Unknown schedule';
    }
}
