@extends('layouts.app')

@section('title', 'Stock Report')
@section('breadcrumb', 'Stock Report')

@section('content')
<div class="p-4 sm:p-5 lg:p-7 space-y-5">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 font-display">Stock Report</h1>
            <p class="text-sm text-gray-500 mt-0.5">Current inventory and expiry status</p>
        </div>
        <a href="{{ route('pharmacy.inventory') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
            View Inventory
        </a>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Medicine</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Unit</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total Stock</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reorder Level</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nearest Expiry</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($items as $item)
                    @php
                        $totalStock  = $item->stocks->sum('quantity_available');
                        $expiryBatch = $item->stocks->where('quantity_available', '>', 0)->sortBy('expiry_date')->first();
                        $isLow       = $totalStock <= ($item->reorder_level ?? 10);
                        $isExpiring  = $expiryBatch && $expiryBatch->expiry_date <= $soon && $expiryBatch->expiry_date >= $today;
                        $isExpired   = $expiryBatch && $expiryBatch->expiry_date < $today;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">{{ $item->name }}</p>
                            <p class="text-xs text-gray-400">{{ $item->generic_name ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->unit }}</td>
                        <td class="px-4 py-3 font-semibold {{ $isLow ? 'text-red-600' : 'text-gray-900' }}">{{ $totalStock }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $item->reorder_level ?? 10 }}</td>
                        <td class="px-4 py-3">
                            @if($totalStock == 0)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Out of Stock</span>
                            @elseif($isLow)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Low Stock</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">OK</span>
                            @endif
                            @if($isExpired)
                                <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Expired</span>
                            @elseif($isExpiring)
                                <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">Expiring Soon</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            @if($expiryBatch)
                                {{ \Carbon\Carbon::parse($expiryBatch->expiry_date)->format('M Y') }}
                                (Batch: {{ $expiryBatch->batch_number }})
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">No medicines in inventory.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
