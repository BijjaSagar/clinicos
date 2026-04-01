<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClinicSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    /**
     * Pricing tiers exposed to views (amount in INR / month equivalent).
     */
    private array $plans = [
        'solo' => [
            'label'    => 'Solo',
            'amount'   => 999,
            'currency' => 'INR',
            'features' => [
                '1 doctor',
                'Up to 500 patients',
                'Appointments & EMR',
                'Basic billing',
                'WhatsApp reminders',
            ],
        ],
        'small' => [
            'label'    => 'Small Clinic',
            'amount'   => 1999,
            'currency' => 'INR',
            'features' => [
                'Up to 5 doctors',
                'Up to 5,000 patients',
                'All Solo features',
                'Lab integration',
                'GST reports',
                'Analytics',
            ],
        ],
        'group' => [
            'label'    => 'Group Practice',
            'amount'   => 3999,
            'currency' => 'INR',
            'features' => [
                'Up to 20 doctors',
                'Unlimited patients',
                'All Small features',
                'Multi-location',
                'AI documentation',
                'Insurance / TPA billing',
                'ABDM integration',
            ],
        ],
        'enterprise' => [
            'label'    => 'Enterprise',
            'amount'   => 7999,
            'currency' => 'INR',
            'features' => [
                'Unlimited doctors',
                'Unlimited patients',
                'All Group features',
                'Dedicated support',
                'Custom EMR builder',
                'SLA guarantee',
                'White-label option',
            ],
        ],
    ];

    // ─── Actions ──────────────────────────────────────────────────────────────

    /**
     * GET /subscription
     * Subscription dashboard for the current clinic.
     */
    public function index()
    {
        Log::info('SubscriptionController@index', ['user' => auth()->id()]);

        $clinicId = auth()->user()->clinic_id;

        $subscription = ClinicSubscription::where('clinic_id', $clinicId)
            ->latest()
            ->first();

        return view('subscriptions.index', [
            'subscription' => $subscription,
            'plans'        => $this->plans,
        ]);
    }

    /**
     * POST /subscription
     * Create a new subscription (starts in trial).
     */
    public function create(Request $request)
    {
        Log::info('SubscriptionController@create', ['user' => auth()->id(), 'input' => $request->only('plan', 'billing_cycle')]);

        $validated = $request->validate([
            'plan'          => ['required', 'in:solo,small,group,enterprise'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,annual'],
        ]);

        $clinicId = auth()->user()->clinic_id;
        $plan     = $validated['plan'];
        $cycle    = $validated['billing_cycle'];

        $baseAmount = $this->plans[$plan]['amount'];

        // Apply billing-cycle multiplier / discount
        $amount = match ($cycle) {
            'quarterly' => $baseAmount * 3 * 0.95,   // 5 % discount
            'annual'    => $baseAmount * 12 * 0.85,  // 15 % discount
            default     => $baseAmount,
        };

        $subscription = ClinicSubscription::create([
            'clinic_id'     => $clinicId,
            'plan'          => $plan,
            'billing_cycle' => $cycle,
            'status'        => 'trial',
            'amount'        => $amount,
            'currency'      => 'INR',
            'trial_ends_at' => now()->addDays(14),
            'auto_renew'    => true,
        ]);

        Log::info('SubscriptionController@create subscription created', [
            'subscription_id' => $subscription->id,
            'clinic_id'       => $clinicId,
            'plan'            => $plan,
        ]);

        return response()->json([
            'success'      => true,
            'subscription' => $subscription,
            'message'      => 'Your 14-day free trial has started.',
        ]);
    }

    /**
     * DELETE /subscription/{subscription}
     * Cancel a subscription.
     */
    public function cancel(ClinicSubscription $subscription)
    {
        Log::info('SubscriptionController@cancel', [
            'user'            => auth()->id(),
            'subscription_id' => $subscription->id,
        ]);

        $subscription->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Log::info('SubscriptionController@cancel subscription cancelled', [
            'subscription_id' => $subscription->id,
        ]);

        return redirect()->route('subscription.index')
            ->with('success', 'Your subscription has been cancelled.');
    }

    /**
     * POST /subscription/webhook
     * Handle Razorpay subscription webhook events.
     */
    public function webhook(Request $request)
    {
        $payload   = $request->all();
        $eventType = $payload['event'] ?? 'unknown';

        Log::info('SubscriptionController@webhook received', [
            'event'   => $eventType,
            'payload' => $payload,
        ]);

        try {
            match ($eventType) {
                'subscription.charged'   => $this->handleSubscriptionCharged($payload),
                'subscription.cancelled' => $this->handleSubscriptionCancelled($payload),
                default => Log::info('SubscriptionController@webhook unhandled event', ['event' => $eventType]),
            };
        } catch (\Throwable $e) {
            Log::error('SubscriptionController@webhook processing error', [
                'event' => $eventType,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok']);
    }

    // ─── Webhook sub-handlers ─────────────────────────────────────────────────

    private function handleSubscriptionCharged(array $payload): void
    {
        $razorpaySubId = $payload['payload']['subscription']['entity']['id'] ?? null;

        if (!$razorpaySubId) {
            Log::warning('SubscriptionController@webhook subscription.charged missing subscription id');
            return;
        }

        $subscription = ClinicSubscription::where('razorpay_subscription_id', $razorpaySubId)->first();

        if (!$subscription) {
            Log::warning('SubscriptionController@webhook subscription.charged subscription not found', [
                'razorpay_subscription_id' => $razorpaySubId,
            ]);
            return;
        }

        $entity             = $payload['payload']['subscription']['entity'];
        $currentStart       = isset($entity['current_start']) ? \Carbon\Carbon::createFromTimestamp($entity['current_start']) : now();
        $currentEnd         = isset($entity['current_end'])   ? \Carbon\Carbon::createFromTimestamp($entity['current_end'])   : now()->addMonth();

        $subscription->update([
            'status'               => 'active',
            'current_period_start' => $currentStart,
            'current_period_end'   => $currentEnd,
            'next_billing_date'    => $currentEnd->toDateString(),
        ]);

        Log::info('SubscriptionController@webhook subscription.charged processed', [
            'subscription_id'      => $subscription->id,
            'razorpay_sub_id'      => $razorpaySubId,
            'current_period_start' => $currentStart,
            'current_period_end'   => $currentEnd,
        ]);
    }

    private function handleSubscriptionCancelled(array $payload): void
    {
        $razorpaySubId = $payload['payload']['subscription']['entity']['id'] ?? null;

        if (!$razorpaySubId) {
            Log::warning('SubscriptionController@webhook subscription.cancelled missing subscription id');
            return;
        }

        $subscription = ClinicSubscription::where('razorpay_subscription_id', $razorpaySubId)->first();

        if (!$subscription) {
            Log::warning('SubscriptionController@webhook subscription.cancelled subscription not found', [
                'razorpay_subscription_id' => $razorpaySubId,
            ]);
            return;
        }

        $subscription->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Log::info('SubscriptionController@webhook subscription.cancelled processed', [
            'subscription_id' => $subscription->id,
            'razorpay_sub_id' => $razorpaySubId,
        ]);
    }
}
