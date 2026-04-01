@extends('layouts.app')

@section('title', 'Subscription')
@section('breadcrumb', 'Subscription')

@section('content')
<div class="p-6 space-y-6">

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm">
            <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Current Subscription Status Card ─────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Current Subscription</h2>
                <p class="text-sm text-gray-500 mt-0.5">Manage your ClinicOS plan and billing</p>
            </div>

            @if ($subscription)
                @if ($subscription->status === 'trial' && $subscription->trial_ends_at?->isFuture())
                    <span class="inline-flex items-center gap-1.5 bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1 rounded-full">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        Trial
                    </span>
                @elseif ($subscription->status === 'active')
                    <span class="inline-flex items-center gap-1.5 bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        Active
                    </span>
                @elseif ($subscription->status === 'cancelled')
                    <span class="inline-flex items-center gap-1.5 bg-red-100 text-red-800 text-xs font-semibold px-3 py-1 rounded-full">
                        Cancelled
                    </span>
                @elseif ($subscription->status === 'paused')
                    <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-700 text-xs font-semibold px-3 py-1 rounded-full">
                        Paused
                    </span>
                @elseif ($subscription->status === 'expired')
                    <span class="inline-flex items-center gap-1.5 bg-red-100 text-red-700 text-xs font-semibold px-3 py-1 rounded-full">
                        Expired
                    </span>
                @endif
            @endif
        </div>

        @if ($subscription)
            <div class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Plan</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 capitalize">
                        {{ $plans[$subscription->plan]['label'] ?? ucfirst($subscription->plan) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Status</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 capitalize">{{ $subscription->status }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Amount</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        ₹{{ number_format($subscription->amount, 0) }}
                        <span class="text-gray-400 font-normal text-xs">/ {{ $subscription->billing_cycle }}</span>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">
                        @if ($subscription->status === 'trial')
                            Trial Ends
                        @else
                            Next Billing
                        @endif
                    </p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        @if ($subscription->status === 'trial' && $subscription->trial_ends_at)
                            {{ $subscription->trial_ends_at->format('d M Y') }}
                            <span class="text-amber-600 text-xs">({{ $subscription->trial_ends_at->diffForHumans() }})</span>
                        @elseif ($subscription->next_billing_date)
                            {{ $subscription->next_billing_date->format('d M Y') }}
                        @else
                            —
                        @endif
                    </p>
                </div>
            </div>

            @if (in_array($subscription->status, ['active', 'trial']))
                <div class="mt-5 pt-5 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">
                        @if ($subscription->current_period_end)
                            {{ $subscription->daysUntilRenewal() }} day(s) remaining in current period
                        @endif
                    </p>
                    <form method="POST" action="{{ route('subscription.cancel', $subscription) }}"
                          onsubmit="return confirm('Are you sure you want to cancel your subscription?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="text-sm text-red-600 hover:text-red-800 font-medium transition-colors">
                            Cancel subscription
                        </button>
                    </form>
                </div>
            @endif
        @else
            <div class="mt-5 flex items-center gap-3 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3">
                <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-blue-800">You don't have an active subscription yet. Choose a plan below to start your free 14-day trial.</p>
            </div>
        @endif
    </div>

    {{-- ── Plan Comparison Table ──────────────────────────────────────────── --}}
    <div>
        <h3 class="text-base font-semibold text-gray-900 mb-4">Choose a Plan</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach ($plans as $key => $plan)
                @php
                    $isCurrent = $subscription && $subscription->plan === $key && in_array($subscription->status, ['active', 'trial']);
                    $isPopular  = $key === 'small';
                @endphp

                <div class="relative bg-white rounded-xl border {{ $isCurrent ? 'border-blue-500 ring-2 ring-blue-500' : 'border-gray-200' }} p-5 flex flex-col">
                    @if ($isPopular && !$isCurrent)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs font-bold px-3 py-0.5 rounded-full">
                            Most Popular
                        </span>
                    @endif
                    @if ($isCurrent)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-green-500 text-white text-xs font-bold px-3 py-0.5 rounded-full">
                            Current Plan
                        </span>
                    @endif

                    <h4 class="text-sm font-bold text-gray-900">{{ $plan['label'] }}</h4>
                    <p class="mt-1">
                        <span class="text-2xl font-extrabold text-gray-900">₹{{ number_format($plan['amount']) }}</span>
                        <span class="text-xs text-gray-500">/mo</span>
                    </p>

                    <ul class="mt-4 space-y-2 flex-1">
                        @foreach ($plan['features'] as $feature)
                            <li class="flex items-start gap-2 text-xs text-gray-600">
                                <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-5">
                        @if ($isCurrent)
                            <button disabled
                                    class="w-full text-sm font-semibold py-2 rounded-lg bg-green-50 text-green-700 border border-green-200 cursor-default">
                                Current Plan
                            </button>
                        @else
                            <button type="button"
                                    onclick="openSubscribeModal('{{ $key }}', '{{ $plan['label'] }}', {{ $plan['amount'] }})"
                                    class="w-full text-sm font-semibold py-2 rounded-lg {{ $isPopular ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-800' }} transition-colors">
                                @if ($subscription && in_array($subscription->status, ['active', 'trial']))
                                    @php
                                        $planOrder = array_keys($plans);
                                        $currentIdx = array_search($subscription->plan, $planOrder);
                                        $targetIdx  = array_search($key, $planOrder);
                                    @endphp
                                    {{ $targetIdx > $currentIdx ? 'Upgrade' : 'Downgrade' }}
                                @else
                                    Start Free Trial
                                @endif
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-3 text-xs text-gray-500 text-center">
            All plans include a 14-day free trial. Quarterly billing saves 5%, annual billing saves 15%.
        </p>
    </div>
</div>

{{-- ── Subscribe Modal ────────────────────────────────────────────────────── --}}
<div id="subscribeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm px-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-gray-900" id="modalTitle">Subscribe</h3>
            <button onclick="closeSubscribeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="subscribeForm" method="POST" action="{{ route('subscription.create') }}">
            @csrf
            <input type="hidden" name="plan" id="modalPlan" />

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle</label>
                <select name="billing_cycle" id="modalBillingCycle"
                        class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly (save 5%)</option>
                    <option value="annual">Annual (save 15%)</option>
                </select>
            </div>

            <div class="bg-blue-50 rounded-lg px-4 py-3 mb-5">
                <p class="text-sm text-blue-800">
                    Your 14-day free trial starts immediately. No credit card required upfront.
                </p>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeSubscribeModal()"
                        class="flex-1 text-sm font-semibold py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 text-sm font-semibold py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                    Start Free Trial
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openSubscribeModal(plan, label, amount) {
    document.getElementById('modalPlan').value = plan;
    document.getElementById('modalTitle').textContent = 'Subscribe to ' + label + ' — ₹' + amount.toLocaleString() + '/mo';
    document.getElementById('subscribeModal').classList.remove('hidden');
}

function closeSubscribeModal() {
    document.getElementById('subscribeModal').classList.add('hidden');
}

document.getElementById('subscribeModal').addEventListener('click', function (e) {
    if (e.target === this) closeSubscribeModal();
});
</script>
@endsection
