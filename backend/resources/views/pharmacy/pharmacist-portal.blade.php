@extends('layouts.app')

@section('title', 'Pharmacist Work Queue')

@section('content')
<div class="p-4 sm:p-6 lg:p-8 space-y-6" x-data="{ dispensingOpen: false, selectedRx: null }">

    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-xl bg-teal-600 flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Pharmacist Work Queue</h1>
                    <p class="text-sm text-gray-500">Prescription dispensing &amp; stock management portal</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0">
            <span class="text-xs text-gray-400 font-medium">{{ now()->format('D, d M Y') }}</span>
        </div>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Pending Prescriptions --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-red-500"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Pending Rx</p>
                    <p class="text-3xl font-extrabold text-red-600 leading-none">{{ $pendingCount ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Awaiting dispensing</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Dispensed Today --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-emerald-500"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Dispensed Today</p>
                    <p class="text-3xl font-extrabold text-emerald-600 leading-none">{{ $dispensedToday ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Completed today</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Low Stock Alerts --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-orange-500"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Low Stock Alerts</p>
                    <p class="text-3xl font-extrabold text-orange-600 leading-none">{{ $lowStockItems->count() }}</p>
                    <p class="text-xs text-gray-400 mt-2">Below reorder level</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-orange-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Near Expiry --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-gray-400"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Near Expiry</p>
                    <p class="text-3xl font-extrabold text-gray-700 leading-none">{{ $nearExpiryItems->count() }}</p>
                    <p class="text-xs text-gray-400 mt-2">Expiring within 30 days</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Two-Column Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Left (65%) - Prescription Queue --}}
        <div class="lg:col-span-3 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h3 class="text-sm font-bold text-gray-900">Prescription Queue</h3>
                    @if($pendingCount > 0)
                    <span class="relative flex items-center gap-1.5 px-2.5 py-0.5 bg-red-50 text-red-700 text-xs font-semibold rounded-full ring-1 ring-red-200">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                        </span>
                        {{ $pendingCount }} pending
                    </span>
                    @endif
                </div>
                <a href="{{ route('pharmacy.history') }}" class="text-xs font-semibold text-teal-600 hover:text-teal-700">View History &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50/70 border-b border-gray-100">
                            <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Doctor</th>
                            <th class="px-5 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-5 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-5 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentDispensing ?? [] as $rx)
                        @php
                            $rxStatus = $rx->status ?? 'pending';
                            $statusConfig = [
                                'pending'    => ['label' => 'Pending',    'class' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-300'],
                                'dispensing'  => ['label' => 'Dispensing',  'class' => 'bg-blue-100 text-blue-800 ring-1 ring-blue-300'],
                                'dispensed'  => ['label' => 'Dispensed',  'class' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300'],
                            ];
                            $sc = $statusConfig[$rxStatus] ?? $statusConfig['pending'];
                            $priority = $rx->priority ?? 'routine';
                            $priorityConfig = [
                                'stat'    => ['label' => 'STAT',    'class' => 'bg-red-100 text-red-700 ring-1 ring-red-300'],
                                'urgent'  => ['label' => 'Urgent',  'class' => 'bg-orange-100 text-orange-700 ring-1 ring-orange-300'],
                                'routine' => ['label' => 'Routine', 'class' => 'bg-gray-100 text-gray-600 ring-1 ring-gray-200'],
                            ];
                            $pc = $priorityConfig[$priority] ?? $priorityConfig['routine'];
                        @endphp
                        <tr class="hover:bg-teal-50/30 transition-colors group {{ $priority === 'stat' ? 'border-l-4 border-l-red-500' : '' }}">
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-gray-900 group-hover:text-teal-700 transition-colors">
                                    {{ $rx->patient->name ?? $rx->patient_name ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $rx->dispensing_number ?? '' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-700">{{ $rx->doctor->name ?? $rx->doctor_name ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-teal-50 text-teal-700 font-bold text-sm rounded-lg ring-1 ring-teal-200">
                                    {{ $rx->items_count ?? $rx->items->count() ?? 0 }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $pc['class'] }}">
                                    {{ $pc['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-xs text-gray-500">
                                    {{ $rx->created_at ? $rx->created_at->format('h:i A') : '—' }}
                                </p>
                                <p class="text-xs text-gray-400">{{ $rx->created_at ? $rx->created_at->diffForHumans() : '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $sc['class'] }}">
                                    @if($rxStatus === 'pending')
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                    @endif
                                    {{ $sc['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if($rxStatus !== 'dispensed')
                                <a href="{{ route('pharmacy.dispense.form') }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                    </svg>
                                    Dispense
                                </a>
                                @else
                                <span class="text-xs text-gray-400">Done</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-600">No pending prescriptions</p>
                                    <p class="text-xs text-gray-400">All prescriptions have been dispensed</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Right (35%) - Quick Actions + Alerts --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Quick Actions --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 space-y-3">
                <h3 class="text-sm font-bold text-gray-900 mb-3">Quick Actions</h3>

                {{-- Scan Barcode --}}
                <div class="flex items-center gap-4 p-4 bg-gray-50 border border-gray-200 rounded-xl">
                    <div class="w-11 h-11 rounded-xl bg-gray-200 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700">Scan Barcode</p>
                        <p class="text-xs text-gray-400">Scanner integration coming soon</p>
                    </div>
                </div>

                {{-- Stock In --}}
                <a href="{{ route('pharmacy.inventory') }}"
                   class="flex items-center gap-4 p-4 bg-teal-50 border border-teal-200 rounded-xl hover:bg-teal-100 transition-colors group">
                    <div class="w-11 h-11 rounded-xl bg-teal-600 flex items-center justify-center flex-shrink-0 shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-teal-800 group-hover:text-teal-900">Stock In / Inventory</p>
                        <p class="text-xs text-teal-600">Manage stock and add new items</p>
                    </div>
                </a>
            </div>

            {{-- Low Stock Items --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-gray-900">Low Stock</h3>
                        @if($lowStockItems->count() > 0)
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-orange-500"></span>
                        </span>
                        @endif
                    </div>
                    <a href="{{ route('pharmacy.inventory') }}?low_stock=1" class="text-xs font-semibold text-teal-600 hover:text-teal-700">View All &rarr;</a>
                </div>
                <div class="p-4 space-y-2">
                    @forelse($lowStockItems->take(5) as $item)
                    @php
                        $stockPercent = ($item->reorder_level > 0) ? min(100, round(($item->current_stock / $item->reorder_level) * 100)) : 0;
                        $barColor = $stockPercent <= 25 ? 'bg-red-500' : ($stockPercent <= 60 ? 'bg-orange-500' : 'bg-amber-500');
                        $barBg = $stockPercent <= 25 ? 'bg-red-100' : ($stockPercent <= 60 ? 'bg-orange-100' : 'bg-amber-100');
                    @endphp
                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $item->name }}</p>
                            <span class="text-xs font-bold {{ $stockPercent <= 25 ? 'text-red-600' : 'text-orange-600' }}">
                                {{ $item->current_stock ?? 0 }}/{{ $item->reorder_level ?? 0 }}
                            </span>
                        </div>
                        <div class="w-full h-2 rounded-full {{ $barBg }}">
                            <div class="h-2 rounded-full {{ $barColor }} transition-all" style="width: {{ $stockPercent }}%"></div>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center gap-2 py-6 text-center">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500">All stock levels OK</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Near Expiry Items --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900">Near Expiry (30 Days)</h3>
                </div>
                <div class="p-4 space-y-2">
                    @forelse($nearExpiryItems->take(5) as $expItem)
                    @php
                        $daysLeft = now()->diffInDays(\Carbon\Carbon::parse($expItem->expiry_date));
                        $urgencyClass = $daysLeft <= 7 ? 'border-red-200 bg-red-50' : ($daysLeft <= 15 ? 'border-orange-200 bg-orange-50' : 'border-amber-200 bg-amber-50');
                        $badgeClass = $daysLeft <= 7 ? 'bg-red-100 text-red-700' : ($daysLeft <= 15 ? 'bg-orange-100 text-orange-700' : 'bg-amber-100 text-amber-700');
                    @endphp
                    <div class="flex items-center justify-between p-3 rounded-xl border {{ $urgencyClass }}">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $expItem->pharmacyItem->name ?? $expItem->item_name ?? '—' }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Batch: {{ $expItem->batch_number ?? '—' }} &middot; Qty: {{ $expItem->quantity ?? $expItem->quantity_available ?? 0 }}
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold flex-shrink-0 {{ $badgeClass }}">
                            {{ $daysLeft }}d left
                        </span>
                    </div>
                    @empty
                    <div class="flex flex-col items-center gap-2 py-6 text-center">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500">No items expiring soon</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Activity Timeline --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-900">Recent Dispensing Activity</h3>
        </div>
        <div class="p-6">
            @if(($recentDispensing ?? collect())->count() > 0)
            <div class="relative">
                <div class="absolute left-4 top-2 bottom-2 w-0.5 bg-gray-200"></div>
                <div class="space-y-5">
                    @foreach(($recentDispensing ?? collect())->take(10) as $activity)
                    <div class="relative flex items-start gap-4 pl-2">
                        <div class="relative z-10 w-5 h-5 rounded-full bg-teal-100 border-2 border-teal-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <div class="w-1.5 h-1.5 rounded-full bg-teal-500"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ $activity->patient->name ?? $activity->patient_name ?? 'Unknown' }}
                                </p>
                                <span class="text-xs text-gray-400">&middot;</span>
                                <span class="text-xs text-gray-500">{{ $activity->items_count ?? ($activity->items->count() ?? 0) }} items</span>
                                @if($activity->total ?? null)
                                <span class="text-xs text-gray-400">&middot;</span>
                                <span class="text-xs font-semibold text-gray-700">{{ "\u{20B9}" }}{{ number_format($activity->total, 0) }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <p class="text-xs text-gray-400">
                                    {{ $activity->created_at ? $activity->created_at->format('h:i A') : '' }}
                                    &middot; by {{ $activity->dispensedBy->name ?? 'Staff' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="flex flex-col items-center gap-3 py-8 text-center">
                <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500">No recent activity</p>
                <p class="text-xs text-gray-400">Dispensing activity will appear here</p>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
