<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookEvent extends Model
{
    protected $table = 'razorpay_webhook_events';

    protected $fillable = [
        'event_id',
        'event_type',
        'payload_json',
        'payload_hash',
        'invoice_id',
        'razorpay_payment_id',
        'processed_at',
        'processing_note',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (RazorpayWebhookEvent $row) {
            Log::info('RazorpayWebhookEvent creating', [
                'event_id' => $row->event_id,
                'event_type' => $row->event_type,
            ]);
        });
    }
}
