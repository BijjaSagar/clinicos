<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class GstSacCode extends Model
{
    protected $table = 'gst_sac_codes';

    protected $primaryKey = 'sac_code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'sac_code',
        'description',
        'service_category',
        'gst_rate',
        'is_exempt',
        'notes',
    ];

    protected $casts = [
        'gst_rate' => 'decimal:2',
        'is_exempt' => 'boolean',
    ];

    public function scopeExempt($query)
    {
        return $query->where('is_exempt', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('service_category', $category);
    }

    public static function getGstRate(string $sacCode): float
    {
        $code = self::find($sacCode);
        
        if (!$code) {
            Log::warning('SAC code not found, defaulting to 18%', ['sac_code' => $sacCode]);
            return 18.00;
        }

        return $code->is_exempt ? 0.00 : $code->gst_rate;
    }

    public static function isExempt(string $sacCode): bool
    {
        $code = self::find($sacCode);
        return $code ? $code->is_exempt : false;
    }
}
