<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Payment extends Model
{
    protected $table = 'payments';

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'invoice_id',
        'patient_id',
        'amount',
        'payment_method',
        'payment_date',
        'razorpay_payment_id',
        'razorpay_order_id',
        'razorpay_signature',
        'transaction_ref',
        'notes',
        'recorded_by',
        'razorpay_refund_id',
        'refund_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    const METHOD_UPI = 'upi';
    const METHOD_CARD = 'card';
    const METHOD_CASH = 'cash';
    const METHOD_NETBANKING = 'netbanking';
    const METHOD_WALLET = 'wallet';
    const METHOD_INSURANCE = 'insurance';
    const METHOD_ADVANCE = 'advance';

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            Log::info('Creating payment', [
                'invoice_id' => $payment->invoice_id,
                'amount' => $payment->amount,
                'method' => $payment->payment_method
            ]);
        });

        static::created(function (Payment $payment) {
            Log::info('Payment created', [
                'id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'amount' => $payment->amount
            ]);
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('payment_date', $date);
    }

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->whereMonth('payment_date', $month)
                     ->whereYear('payment_date', $year);
    }

    public function isRazorpayPayment(): bool
    {
        return !empty($this->razorpay_payment_id);
    }

    public function isVerified(): bool
    {
        if (!$this->isRazorpayPayment()) {
            return true;
        }
        return !empty($this->razorpay_signature);
    }
}
