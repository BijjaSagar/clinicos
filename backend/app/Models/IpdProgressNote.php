<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdProgressNote extends Model
{
    protected $table = 'ipd_progress_notes';

    protected $guarded = [];

    protected $casts = [
        'note_date' => 'date',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id');
    }

    // Alias for compatibility with views that use ->author
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
