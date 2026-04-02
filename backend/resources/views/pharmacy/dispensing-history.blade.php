@extends('layouts.app')

@section('title', 'Dispensing History')
@section('breadcrumb', 'Dispensing History')

@section('content')
<div class="p-4 sm:p-5 lg:p-7 space-y-5">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 font-display">Dispensing History</h1>
            <p class="text-sm text-gray-500 mt-0.5">All medicine dispensing records</p>
        </div>
        <a href="{{ route('pharmacy.dispense.form') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:shadow-lg"
           style="background:linear-gradient(135deg,#1447E6,#0891B2);">
            + Dispense Medicine
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('pharmacy.history') }}" class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="Patient name or RX number…">
            <input type="date" name="from" value="{{ request('from') }}"
                   class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <input type="date" name="to" value="{{ request('to') }}"
                   class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white rounded-lg" style="background:#1447E6;">Filter</button>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Receipt No.</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Patient</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Payment</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dispensed By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($dispensings as $d)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-semibold text-blue-600">{{ $d->dispensing_number }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">{{ $d->patient?->name ?? 'Walk-in' }}</p>
                            <p class="text-xs text-gray-400">{{ $d->patient?->phone ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $d->items->count() }} item(s)</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">₹{{ number_format($d->total_amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ ucfirst($d->payment_mode) }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($d->dispensed_at)->format('d M Y, H:i') }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $d->dispensedBy?->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400">
                            No dispensing records found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($dispensings->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $dispensings->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
