@extends('layouts.app')

@section('title', 'Pharmacy')
@section('breadcrumb', 'Pharmacy')

@section('content')
<div class="p-4 sm:p-5 lg:p-7 space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 font-display">Pharmacy</h1>
            <p class="text-sm text-gray-500 mt-0.5">Medicine dispensing, inventory & stock management</p>
        </div>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium" style="background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5">
            <p class="text-xs font-medium text-gray-400 mb-1.5">Total Medicines</p>
            <p class="font-display font-extrabold text-2xl sm:text-3xl text-gray-900 leading-none">{{ $stats['total_medicines'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">In catalog</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5">
            <p class="text-xs font-medium text-gray-400 mb-1.5">Low Stock Alerts</p>
            <p class="font-display font-extrabold text-2xl sm:text-3xl leading-none" style="color:#d97706;">{{ $stats['low_stock_count'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">Below reorder level</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5">
            <p class="text-xs font-medium text-gray-400 mb-1.5">Today's Dispensing</p>
            <p class="font-display font-extrabold text-2xl sm:text-3xl leading-none" style="color:#1447E6;">{{ $stats['dispensed_today'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">Patients served</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5">
            <p class="text-xs font-medium text-gray-400 mb-1.5">Monthly Revenue</p>
            <p class="font-display font-extrabold text-xl sm:text-3xl leading-none" style="color:#059669;">₹{{ $stats['monthly_revenue'] ?? '0' }}</p>
            <p class="text-xs text-gray-400 mt-2">This month</p>
        </div>
    </div>

    {{-- Pending Prescriptions Queue --}}
    @if(($pendingPrescriptions ?? collect())->isNotEmpty())
    <div class="bg-white rounded-2xl border border-amber-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-amber-100 bg-amber-50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="font-semibold text-amber-900 text-sm">Pending Prescriptions</h3>
                <span class="px-2 py-0.5 bg-amber-200 text-amber-800 text-xs font-bold rounded-full">{{ $pendingPrescriptions->count() }}</span>
            </div>
            <a href="{{ route('pharmacy.dispensing') }}" class="text-xs text-amber-700 font-semibold hover:underline">Open Dispensing →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($pendingPrescriptions as $rx)
            <div class="px-5 py-3 flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm font-semibold text-gray-900">{{ $rx->patient_name }}</p>
                        @if($rx->patient_phone)
                        <span class="text-xs text-gray-400">{{ $rx->patient_phone }}</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ \Carbon\Carbon::parse($rx->created_at)->diffForHumans() }}
                        @if($rx->diagnosis_text) · {{ Str::limit($rx->diagnosis_text, 40) }} @endif
                    </p>
                    @if($rx->drugs->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                        @foreach($rx->drugs->take(4) as $drug)
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-md">
                            {{ $drug->drug_name }} {{ $drug->dose ?? '' }}
                        </span>
                        @endforeach
                        @if($rx->drugs->count() > 4)
                        <span class="text-xs text-gray-400">+{{ $rx->drugs->count() - 4 }} more</span>
                        @endif
                    </div>
                    @endif
                </div>
                <a href="{{ route('pharmacy.dispensing') }}?patient_id={{ $rx->patient_name }}"
                   class="flex-shrink-0 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition-colors">
                    Dispense
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @php
        $quickActions = [
            ['label' => 'Dispense Medicine', 'href' => route('pharmacy.dispense.form'),  'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', 'gradient' => '#1447E6,#0891B2'],
            ['label' => 'Add Stock',         'href' => route('pharmacy.inventory'),   'icon' => 'M12 4v16m8-8H4', 'gradient' => '#059669,#0d9488'],
            ['label' => 'View Inventory',    'href' => route('pharmacy.inventory'),   'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16', 'gradient' => '#7c3aed,#a855f7'],
            ['label' => 'Reports',           'href' => '#',                           'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'gradient' => '#d97706,#ef4444'],
        ];
        @endphp
        @foreach($quickActions as $action)
        <a href="{{ $action['href'] }}"
           class="flex flex-col items-center gap-3 p-4 bg-white border border-gray-200 rounded-xl hover:shadow-md transition-all hover:border-gray-300 text-center group">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white"
                 style="background:linear-gradient(135deg,{{ $action['gradient'] }});">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['icon'] }}"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-gray-700 group-hover:text-gray-900 leading-tight">{{ $action['label'] }}</span>
        </a>
        @endforeach
    </div>

    {{-- Two-column: Recent Dispensing + Low Stock --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Recent Dispensing --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900">Recent Dispensing</h3>
                <a href="{{ route('pharmacy.history') }}" class="text-xs font-semibold" style="color:#1447E6;">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Patient</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Items</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentDispensing ?? [] as $dispensing)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-gray-900">{{ $dispensing->patient_name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $dispensing->dispensing_number ?? '' }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $dispensing->items_count ?? 0 }} items</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">₹{{ number_format($dispensing->total_amount ?? 0) }}</td>
                            <td class="px-4 py-3 text-xs text-gray-400">
                                {{ $dispensing->created_at ? \Carbon\Carbon::parse($dispensing->created_at)->format('H:i') : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-400">
                                No dispensing records today.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Low Stock Alerts --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900">Low Stock Alerts</h3>
                <a href="{{ route('pharmacy.inventory') }}?stock_status=low-stock" class="text-xs font-semibold" style="color:#1447E6;">View all →</a>
            </div>
            <div class="p-4 space-y-2">
                @forelse($lowStockItems ?? [] as $item)
                <div class="flex items-center gap-3 p-3 rounded-xl" style="background:#fffbeb;border:1px solid #fde68a;">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#fef3c7;">
                        <svg class="w-4 h-4" style="color:#d97706;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $item->name ?? '—' }}</p>
                        <p class="text-xs text-gray-500">Current: {{ $item->current_stock ?? 0 }} {{ $item->unit ?? '' }} | Reorder: {{ $item->reorder_level ?? 0 }}</p>
                    </div>
                    <a href="{{ route('pharmacy.inventory') }}" class="flex-shrink-0 text-xs font-semibold px-2.5 py-1 rounded-lg" style="background:#fde68a;color:#92400e;">
                        Restock
                    </a>
                </div>
                @empty
                <div class="flex flex-col items-center gap-2 py-8 text-center">
                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-600">All stock levels are adequate</p>
                    <p class="text-xs text-gray-400">No items below reorder level</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
