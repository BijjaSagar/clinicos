@extends('layouts.guest')
@section('title', 'Trial Expired — Upgrade to Continue')
@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex flex-col items-center justify-center p-6">

    <div class="text-center mb-10">
        <div class="inline-flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-white text-xl" style="background:linear-gradient(135deg,#1447E6,#0891B2);">C</div>
            <h1 class="text-2xl font-bold text-white">ClinicOS</h1>
        </div>
        <div class="w-16 h-16 rounded-full bg-amber-500/20 border-2 border-amber-500 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-3xl font-bold text-white mb-2">Your free trial has ended</h2>
        <p class="text-gray-400 max-w-md mx-auto">Subscribe to continue using ClinicOS. Your data is safe and will be restored immediately after subscribing.</p>
    </div>

    {{-- Plans --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl w-full">
        @php
        $plans = [
            ['key'=>'starter','name'=>'Starter','price'=>'₹2,999','period'=>'/month','desc'=>'Perfect for solo practitioners','color'=>'#1447E6','features'=>['Up to 500 patients','Appointments & EMR','Billing & invoicing','WhatsApp reminders','1 doctor + 2 staff']],
            ['key'=>'professional','name'=>'Professional','price'=>'₹5,999','period'=>'/month','desc'=>'For growing clinics','color'=>'#0891B2','badge'=>'Most Popular','features'=>['Unlimited patients','All Starter features','Multi-doctor support','Analytics & reports','ABDM integration','5 staff users']],
            ['key'=>'hospital','name'=>'Hospital HIMS','price'=>'₹14,999','period'=>'/month','desc'=>'Full hospital management','color'=>'#7c3aed','features'=>['All Professional features','IPD & bed management','Pharmacy module','Lab LIS module','OPD queue system','Unlimited staff']],
        ];
        @endphp
        @foreach($plans as $plan)
        <div class="bg-white rounded-2xl p-6 relative {{ isset($plan['badge']) ? 'ring-2 ring-cyan-500 shadow-cyan-500/20 shadow-xl' : '' }}">
            @if(isset($plan['badge']))
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 text-xs font-bold text-white rounded-full" style="background:linear-gradient(135deg,#1447E6,#0891B2);">{{ $plan['badge'] }}</div>
            @endif
            <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $plan['name'] }}</h3>
            <p class="text-xs text-gray-500 mb-4">{{ $plan['desc'] }}</p>
            <div class="flex items-baseline gap-1 mb-5">
                <span class="text-3xl font-extrabold text-gray-900">{{ $plan['price'] }}</span>
                <span class="text-sm text-gray-400">{{ $plan['period'] }}</span>
            </div>
            <ul class="space-y-2 mb-6">
                @foreach($plan['features'] as $f)
                <li class="flex items-center gap-2 text-sm text-gray-700">
                    <svg class="w-4 h-4 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ $f }}
                </li>
                @endforeach
            </ul>
            <a href="mailto:sales@clinicos.in?subject=Subscription+{{ $plan['name'] }}"
               class="block w-full py-3 rounded-xl text-center text-sm font-bold text-white transition-all hover:opacity-90"
               style="background:linear-gradient(135deg,{{ $plan['color'] }},{{ $plan['color'] }}cc);">
                Subscribe — {{ $plan['price'] }}/mo
            </a>
        </div>
        @endforeach
    </div>

    <div class="mt-8 text-center">
        <p class="text-gray-500 text-sm">Need help? Contact us at <a href="mailto:support@clinicos.in" class="text-cyan-400">support@clinicos.in</a></p>
        <form method="POST" action="{{ route('logout') }}" class="mt-4 inline">
            @csrf
            <button type="submit" class="text-gray-500 hover:text-gray-300 text-sm underline">Log out</button>
        </form>
    </div>
</div>
@endsection
