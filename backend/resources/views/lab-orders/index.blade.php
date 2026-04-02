@extends('layouts.app')

@section('title', 'Lab Orders (External)')

@section('breadcrumb', 'Lab Orders')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Lab Integration</h1>
            <p class="text-sm text-gray-500 mt-0.5">Order tests from major diagnostic labs</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @php
        $statCards = [
            ['label' => 'New Today',    'value' => $stats['new_today'],    'color' => 'blue'],
            ['label' => 'Processing',   'value' => $stats['processing'],   'color' => 'amber'],
            ['label' => 'Ready',        'value' => $stats['ready'],        'color' => 'green'],
            ['label' => 'This Month',   'value' => $stats['total_month'],  'color' => 'purple'],
        ];
        @endphp
        @foreach($statCards as $card)
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $card['label'] }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $card['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Partner Labs --}}
    @if($partnerClinics->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-sm font-semibold text-gray-700 mb-3">Connected Lab Partners</p>
        <div class="flex flex-wrap gap-3">
            @foreach($partnerClinics as $partner)
            <div class="flex items-center gap-2 px-3 py-2 bg-teal-50 rounded-lg border border-teal-100">
                <div class="w-7 h-7 rounded-full bg-teal-600 flex items-center justify-center text-white text-xs font-bold">
                    {{ substr($partner->clinic->name ?? '?', 0, 2) }}
                </div>
                <span class="text-sm font-medium text-gray-800">{{ $partner->clinic->name ?? 'Partner Lab' }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
            <option value="">All Statuses</option>
            <option value="pending"          {{ request('status') === 'pending'          ? 'selected' : '' }}>Pending</option>
            <option value="sample_collected" {{ request('status') === 'sample_collected' ? 'selected' : '' }}>Sample Collected</option>
            <option value="processing"       {{ request('status') === 'processing'       ? 'selected' : '' }}>Processing</option>
            <option value="completed"        {{ request('status') === 'completed'        ? 'selected' : '' }}>Completed</option>
            <option value="cancelled"        {{ request('status') === 'cancelled'        ? 'selected' : '' }}>Cancelled</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200">Filter</button>
        @if(request('status'))
        <a href="{{ route('vendor.index') }}" class="px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-100">Clear</a>
        @endif
    </form>

    {{-- Orders Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Recent Lab Orders</h2>
        </div>
        @if($orders->isEmpty())
        <div class="py-16 text-center">
            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm text-gray-500">No lab orders yet.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Order #</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tests</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($orders as $order)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3 text-sm font-mono font-medium text-gray-900">
                            {{ $order->order_number }}
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-800">
                            {{ $order->patient?->name ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-600">
                            @php
                                $tests = is_string($order->tests) ? json_decode($order->tests, true) : ($order->tests ?? []);
                                $testCount = is_array($tests) ? count($tests) : 0;
                            @endphp
                            {{ $testCount }} test{{ $testCount !== 1 ? 's' : '' }}
                        </td>
                        <td class="px-5 py-3 text-sm font-semibold text-gray-900">
                            ₹{{ number_format($order->total_amount ?? 0, 2) }}
                        </td>
                        <td class="px-5 py-3">
                            @php
                            $statusColors = [
                                'pending'          => 'bg-yellow-50 text-yellow-700',
                                'sample_collected' => 'bg-blue-50 text-blue-700',
                                'processing'       => 'bg-indigo-50 text-indigo-700',
                                'completed'        => 'bg-green-50 text-green-700',
                                'cancelled'        => 'bg-red-50 text-red-700',
                            ];
                            $sc = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $sc }}">
                                {{ ucwords(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-500">
                            {{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y') : '—' }}
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                @if(in_array($order->status, ['pending', 'sample_collected']))
                                <form method="POST" action="{{ route('vendor.accept', $order->id) }}">
                                    @csrf
                                    <button type="submit" class="text-xs px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg font-medium hover:bg-indigo-100 transition-colors">
                                        Accept
                                    </button>
                                </form>
                                @endif
                                @if($order->status === 'processing')
                                <button type="button" onclick="document.getElementById('upload-{{ $order->id }}').classList.remove('hidden')"
                                    class="text-xs px-3 py-1.5 bg-green-50 text-green-700 rounded-lg font-medium hover:bg-green-100 transition-colors">
                                    Upload Result
                                </button>
                                @endif
                                @if($order->result_url)
                                <a href="{{ $order->result_url }}" target="_blank" class="text-xs px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                                    View PDF
                                </a>
                                @endif
                            </div>

                            {{-- Upload result form (hidden) --}}
                            <div id="upload-{{ $order->id }}" class="hidden mt-2">
                                <form method="POST" action="{{ route('vendor.upload', $order->id) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                    @csrf
                                    <input type="file" name="result_file" accept=".pdf" required class="text-xs">
                                    <button type="submit" class="text-xs px-2 py-1 bg-green-600 text-white rounded-lg font-medium">Save</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $orders->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
