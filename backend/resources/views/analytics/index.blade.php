@extends('layouts.app')

@section('title', 'Analytics')
@section('breadcrumb', 'Analytics & Reports')

@section('content')
<div class="p-6 space-y-6">
    {{-- Period Filter --}}
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Analytics Overview</h1>
        <div class="flex gap-2">
            @foreach(['week' => 'Week', 'month' => 'Month', 'quarter' => 'Quarter', 'year' => 'Year'] as $key => $label)
            <a href="?period={{ $key }}" class="px-4 py-2 text-sm font-semibold rounded-lg {{ $period === $key ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    {{-- Revenue Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 font-medium">Total Revenue</p>
            <p class="text-2xl font-extrabold text-gray-900 font-display mt-1">₹{{ number_format($revenue['total'] ?? 0) }}</p>
            <p class="text-xs text-green-600 mt-1">This {{ $period }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 font-medium">Pending Collections</p>
            <p class="text-2xl font-extrabold text-amber-600 font-display mt-1">₹{{ number_format($revenue['pending'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 font-medium">Collected Today</p>
            <p class="text-2xl font-extrabold text-green-600 font-display mt-1">₹{{ number_format($revenue['collected_today'] ?? 0) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Revenue Chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-900">Revenue Trend</h3>
            </div>
            <div class="p-5">
                <div class="h-64 flex items-end justify-between gap-2">
                    @foreach($dailyRevenue as $day)
                    @php
                        $maxRevenue = $dailyRevenue->max('total') ?: 1;
                        $heightPercent = ($day->total / $maxRevenue) * 100;
                    @endphp
                    <div class="flex-1 flex flex-col items-center gap-2">
                        <div class="w-full bg-blue-500 rounded-t-lg transition-all hover:bg-blue-600" style="height: {{ $heightPercent }}%"></div>
                        <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($day->date)->format('d') }}</span>
                    </div>
                    @endforeach
                    @if($dailyRevenue->isEmpty())
                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                        No revenue data for this period
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- GST Summary --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-900">GST Summary</h3>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Taxable Amount</span>
                    <span class="font-semibold text-gray-900">₹{{ number_format($gstSummary['taxable'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">CGST (9%)</span>
                    <span class="font-semibold text-gray-900">₹{{ number_format($gstSummary['cgst'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">SGST (9%)</span>
                    <span class="font-semibold text-gray-900">₹{{ number_format($gstSummary['sgst'] ?? 0) }}</span>
                </div>
                <div class="border-t border-gray-200 pt-4 flex justify-between items-center">
                    <span class="font-semibold text-gray-900">Total GST</span>
                    <span class="font-bold text-blue-600 text-lg">₹{{ number_format(($gstSummary['cgst'] ?? 0) + ($gstSummary['sgst'] ?? 0)) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Appointments Stats --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-900">Appointments</h3>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Total</span>
                    <span class="font-bold text-2xl text-gray-900">{{ $appointments['total'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Completed</span>
                    <span class="font-semibold text-green-600">{{ $appointments['completed'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">No-show Rate</span>
                    <span class="font-semibold text-red-600">{{ $appointments['noshow_rate'] ?? 0 }}%</span>
                </div>
            </div>
        </div>

        {{-- Patients Stats --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-900">Patients</h3>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Total Registered</span>
                    <span class="font-bold text-2xl text-gray-900">{{ $patients['total'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">New ({{ ucfirst($period) }})</span>
                    <span class="font-semibold text-blue-600">{{ $patients['new'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Returning</span>
                    <span class="font-semibold text-gray-900">{{ $patients['returning'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        {{-- Top Services --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-900">Top Services</h3>
            </div>
            <div class="p-5 space-y-3">
                @forelse($topServices as $service)
                <div class="flex items-center justify-between">
                    <span class="text-gray-600 truncate">{{ $service->name }}</span>
                    <span class="px-2.5 py-1 bg-blue-50 text-blue-700 text-xs font-semibold rounded-full">{{ $service->count }}</span>
                </div>
                @empty
                <p class="text-gray-400 text-sm">No services data</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
