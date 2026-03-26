@extends('admin.layouts.app')

@section('title', 'Subscriptions')
@section('subtitle', 'Manage plans and billing')

@section('content')
<div class="space-y-6">
    {{-- Plans Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Trial --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-100 card-hover">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="px-3 py-1 text-xs font-semibold bg-amber-100 text-amber-700 rounded-full">Trial</span>
            </div>
            <h3 class="text-lg font-display font-bold text-slate-900">Free Trial</h3>
            <p class="text-3xl font-display font-bold text-slate-900 mt-2">₹0</p>
            <p class="text-sm text-slate-500 mb-4">30 days</p>
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Active clinics</span>
                    <span class="font-bold text-slate-900">{{ $clinicsByPlan['trial'] ?? 0 }}</span>
                </div>
            </div>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> 1 Doctor</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> 50 Patients</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> Basic Features</li>
            </ul>
        </div>

        {{-- Solo --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-100 card-hover">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <span class="px-3 py-1 text-xs font-semibold bg-blue-100 text-blue-700 rounded-full">Solo</span>
            </div>
            <h3 class="text-lg font-display font-bold text-slate-900">Solo Practice</h3>
            <p class="text-3xl font-display font-bold text-slate-900 mt-2">₹999<span class="text-sm font-normal text-slate-500">/mo</span></p>
            <p class="text-sm text-slate-500 mb-4">₹9,999/year</p>
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Active clinics</span>
                    <span class="font-bold text-slate-900">{{ $clinicsByPlan['solo'] ?? 0 }}</span>
                </div>
            </div>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> 1 Doctor</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> Unlimited Patients</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> WhatsApp Integration</li>
            </ul>
        </div>

        {{-- Small --}}
        <div class="bg-white rounded-2xl p-6 border-2 border-emerald-500 relative card-hover">
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 gradient-green text-white text-xs font-bold rounded-full shadow-lg shadow-emerald-500/30">
                Popular
            </div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                </div>
                <span class="px-3 py-1 text-xs font-semibold bg-emerald-100 text-emerald-700 rounded-full">Small</span>
            </div>
            <h3 class="text-lg font-display font-bold text-slate-900">Small Clinic</h3>
            <p class="text-3xl font-display font-bold text-slate-900 mt-2">₹2,499<span class="text-sm font-normal text-slate-500">/mo</span></p>
            <p class="text-sm text-slate-500 mb-4">₹24,999/year</p>
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Active clinics</span>
                    <span class="font-bold text-slate-900">{{ $clinicsByPlan['small'] ?? 0 }}</span>
                </div>
            </div>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> Up to 3 Doctors</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> Unlimited Patients</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> All Integrations</li>
            </ul>
        </div>

        {{-- Enterprise --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-100 card-hover">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
                    </svg>
                </div>
                <span class="px-3 py-1 text-xs font-semibold bg-indigo-100 text-indigo-700 rounded-full">Enterprise</span>
            </div>
            <h3 class="text-lg font-display font-bold text-slate-900">Enterprise</h3>
            <p class="text-3xl font-display font-bold text-slate-900 mt-2">Custom</p>
            <p class="text-sm text-slate-500 mb-4">Contact sales</p>
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Active clinics</span>
                    <span class="font-bold text-slate-900">{{ $clinicsByPlan['enterprise'] ?? 0 }}</span>
                </div>
            </div>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> Unlimited Doctors</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> Multi-Location</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> Dedicated Support</li>
            </ul>
        </div>
    </div>

    {{-- Revenue Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 border border-slate-100 card-hover">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 gradient-green rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/30">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">MRR (Monthly Recurring)</p>
                    <p class="text-3xl font-display font-bold text-slate-900">₹{{ number_format($mrr) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-slate-100 card-hover">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 gradient-blue rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">ARR (Annual Recurring)</p>
                    <p class="text-3xl font-display font-bold text-slate-900">₹{{ number_format($arr) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-slate-100 card-hover">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 gradient-amber rounded-2xl flex items-center justify-center shadow-lg shadow-amber-500/30">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Active Trials</p>
                    <p class="text-3xl font-display font-bold text-slate-900">{{ $activeTrials }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Paid Subscriptions --}}
        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-display font-bold text-slate-900">Paid Subscriptions</h3>
                <span class="px-3 py-1 text-xs font-semibold bg-emerald-100 text-emerald-700 rounded-full">{{ $paidClinics->count() }} active</span>
            </div>
            <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                @forelse($paidClinics as $clinic)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors" x-data="{ showPlanModal: false }">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <span class="text-indigo-600 font-semibold">{{ substr($clinic->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $clinic->name }}</p>
                            <p class="text-xs text-slate-500">{{ $clinic->owner?->email ?? 'No owner' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @if($clinic->plan === 'solo') bg-blue-100 text-blue-700
                            @elseif($clinic->plan === 'small') bg-emerald-100 text-emerald-700
                            @elseif($clinic->plan === 'group') bg-purple-100 text-purple-700
                            @else bg-indigo-100 text-indigo-700 @endif
                        ">{{ ucfirst($clinic->plan) }}</span>
                        <button @click="showPlanModal = true" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Change Plan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                            </svg>
                        </button>
                    </div>
                    
                    {{-- Plan Change Modal --}}
                    <div x-show="showPlanModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="showPlanModal = false">
                        <div class="fixed inset-0 bg-black/50" @click="showPlanModal = false"></div>
                        <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-sm mx-4 w-full" @click.stop>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4">Change Plan for {{ $clinic->name }}</h3>
                            <form action="{{ route('admin.subscriptions.update-plan', $clinic) }}" method="POST">
                                @csrf
                                <select name="plan" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 mb-4">
                                    <option value="trial" {{ $clinic->plan === 'trial' ? 'selected' : '' }}>Trial</option>
                                    <option value="solo" {{ $clinic->plan === 'solo' ? 'selected' : '' }}>Solo - ₹999/mo</option>
                                    <option value="small" {{ $clinic->plan === 'small' ? 'selected' : '' }}>Small - ₹2,499/mo</option>
                                    <option value="group" {{ $clinic->plan === 'group' ? 'selected' : '' }}>Group - ₹4,999/mo</option>
                                    <option value="enterprise" {{ $clinic->plan === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                                </select>
                                <div class="flex gap-3">
                                    <button type="button" @click="showPlanModal = false" class="flex-1 px-4 py-2 border border-slate-300 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-slate-200 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                    </svg>
                    <p class="text-slate-500">No paid subscriptions yet</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Trials Expiring Soon --}}
        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-display font-bold text-slate-900">Trials Expiring Soon</h3>
                <span class="text-sm text-slate-500">Next 7 days</span>
            </div>
            <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                @forelse($expiringTrials as $clinic)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors" x-data="{ showExtendModal: false }">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $clinic->name }}</p>
                            <p class="text-xs text-slate-500">{{ $clinic->owner?->email ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-sm font-medium text-amber-600">{{ $clinic->trial_ends_at->diffForHumans() }}</p>
                            <p class="text-xs text-slate-500">{{ $clinic->trial_ends_at->format('d M Y') }}</p>
                        </div>
                        <button @click="showExtendModal = true" class="px-3 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">Extend</button>
                    </div>
                    
                    {{-- Extend Trial Modal --}}
                    <div x-show="showExtendModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="showExtendModal = false">
                        <div class="fixed inset-0 bg-black/50" @click="showExtendModal = false"></div>
                        <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-sm mx-4 w-full" @click.stop>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4">Extend Trial for {{ $clinic->name }}</h3>
                            <form action="{{ route('admin.subscriptions.extend-trial', $clinic) }}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Extend by (days)</label>
                                    <input type="number" name="days" value="14" min="1" max="365" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="flex gap-3">
                                    <button type="button" @click="showExtendModal = false" class="flex-1 px-4 py-2 border border-slate-300 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium">Extend</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-slate-200 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-slate-500">No trials expiring soon</p>
                    <p class="text-xs text-slate-400 mt-1">All trials have plenty of time left</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Expired Trials --}}
    @if($expiredTrials->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-display font-bold text-slate-900">Expired Trials</h3>
            <span class="px-3 py-1 text-xs font-semibold bg-red-100 text-red-700 rounded-full">{{ $expiredTrials->count() }} expired</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Clinic</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Owner</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Expired</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($expiredTrials as $clinic)
                    <tr class="hover:bg-slate-50" x-data="{ showConvertModal: false }">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                                    <span class="text-red-600 font-semibold text-xs">{{ substr($clinic->name, 0, 1) }}</span>
                                </div>
                                <span class="text-sm font-medium text-slate-900">{{ $clinic->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-600">{{ $clinic->owner?->email ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-red-600">{{ $clinic->trial_ends_at->diffForHumans() }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="showConvertModal = true" class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">Convert to Paid</button>
                                <form action="{{ route('admin.subscriptions.extend-trial', $clinic) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="days" value="30">
                                    <button type="submit" class="px-3 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">Extend 30 days</button>
                                </form>
                            </div>
                            
                            {{-- Convert Modal --}}
                            <div x-show="showConvertModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="showConvertModal = false">
                                <div class="fixed inset-0 bg-black/50" @click="showConvertModal = false"></div>
                                <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-sm mx-4 w-full text-left" @click.stop>
                                    <h3 class="text-lg font-semibold text-slate-900 mb-4">Convert to Paid Plan</h3>
                                    <form action="{{ route('admin.subscriptions.update-plan', $clinic) }}" method="POST">
                                        @csrf
                                        <select name="plan" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 mb-4">
                                            <option value="solo">Solo - ₹999/mo</option>
                                            <option value="small" selected>Small - ₹2,499/mo</option>
                                            <option value="group">Group - ₹4,999/mo</option>
                                            <option value="enterprise">Enterprise</option>
                                        </select>
                                        <div class="flex gap-3">
                                            <button type="button" @click="showConvertModal = false" class="flex-1 px-4 py-2 border border-slate-300 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                                            <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium">Convert</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Payment Integration Notice --}}
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-2xl p-6">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 gradient-indigo rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-indigo-500/30">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-display font-bold text-indigo-900 mb-2">Razorpay Integration Coming Soon</h3>
                <p class="text-sm text-indigo-700 mb-4">
                    We're integrating Razorpay for automatic subscription payments. Once enabled, you'll be able to:
                </p>
                <ul class="text-sm text-indigo-700 space-y-2 mb-4">
                    <li class="flex items-center gap-2">
                        <span class="w-5 h-5 bg-indigo-200 rounded-full flex items-center justify-center text-indigo-600 text-xs">✓</span>
                        Accept automatic recurring payments via UPI, cards, and net banking
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-5 h-5 bg-indigo-200 rounded-full flex items-center justify-center text-indigo-600 text-xs">✓</span>
                        View payment history and generate invoices automatically
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-5 h-5 bg-indigo-200 rounded-full flex items-center justify-center text-indigo-600 text-xs">✓</span>
                        Handle failed payments with automatic retries
                    </li>
                </ul>
                <p class="text-xs text-indigo-600">
                    Currently, manage clinic plans manually using the controls above.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
