@extends('layouts.app')

@section('title', 'Lab Orders')
@section('breadcrumb', 'Lab Orders')

@section('content')
<div x-data="labOrders()" class="p-4 sm:p-5 lg:p-7 space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 font-display">Lab Orders</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage in-house lab test orders and results</p>
        </div>
        <button @click="showNewOrderModal = true"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:shadow-lg hover:scale-[1.02]"
            style="background:linear-gradient(135deg,#1447E6,#0891B2);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Lab Order
        </button>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium" style="background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5">
            <p class="text-xs font-medium text-gray-400 mb-1.5">Pending</p>
            <p class="font-display font-extrabold text-2xl sm:text-3xl leading-none" style="color:#d97706;">{{ $stats['pending'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">Awaiting sample</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5">
            <p class="text-xs font-medium text-gray-400 mb-1.5">In Progress</p>
            <p class="font-display font-extrabold text-2xl sm:text-3xl leading-none" style="color:#1447E6;">{{ $stats['in_progress'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">Being processed</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5">
            <p class="text-xs font-medium text-gray-400 mb-1.5">Completed Today</p>
            <p class="font-display font-extrabold text-2xl sm:text-3xl leading-none" style="color:#059669;">{{ $stats['completed_today'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">Results ready</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5">
            <p class="text-xs font-medium text-gray-400 mb-1.5">This Month</p>
            <p class="font-display font-extrabold text-2xl sm:text-3xl text-gray-900 leading-none">{{ $stats['total_month'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">Total orders</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <form method="GET" action="{{ route('laboratory.orders') }}" class="flex flex-col sm:flex-row gap-3">
            {{-- Status --}}
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="pending"    {{ request('status') === 'pending'    ? 'selected' : '' }}>Pending</option>
                <option value="sample_collected" {{ request('status') === 'sample_collected' ? 'selected' : '' }}>Sample Collected</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="completed"  {{ request('status') === 'completed'  ? 'selected' : '' }}>Completed</option>
                <option value="cancelled"  {{ request('status') === 'cancelled'  ? 'selected' : '' }}>Cancelled</option>
            </select>

            {{-- Date Range --}}
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">

            {{-- Patient Search --}}
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search patient name, order no…"
                    class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition-colors">
                Filter
            </button>
        </form>
    </div>

    {{-- Orders Table --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">Orders</h3>
            <span class="text-xs text-gray-400">{{ $orders->total() ?? 0 }} total</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Patient</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Tests</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Priority</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Ordered By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($orders as $order)
                    @php
                        $statusMap = [
                            'pending'          => ['label' => 'Pending',          'bg' => '#f1f5f9', 'color' => '#64748b'],
                            'sample_collected' => ['label' => 'Sample Collected', 'bg' => '#eff6ff', 'color' => '#1447e6'],
                            'processing'       => ['label' => 'Processing',       'bg' => '#fffbeb', 'color' => '#d97706'],
                            'completed'        => ['label' => 'Completed',        'bg' => '#ecfdf5', 'color' => '#059669'],
                            'cancelled'        => ['label' => 'Cancelled',        'bg' => '#fff1f2', 'color' => '#dc2626'],
                        ];
                        $s = $statusMap[$order->status] ?? $statusMap['pending'];

                        $priorityMap = [
                            'routine' => ['label' => 'Routine', 'bg' => '#f1f5f9', 'color' => '#64748b'],
                            'urgent'  => ['label' => 'Urgent',  'bg' => '#fffbeb', 'color' => '#d97706'],
                            'stat'    => ['label' => 'STAT',    'bg' => '#fff1f2', 'color' => '#dc2626'],
                        ];
                        $p = $priorityMap[$order->priority ?? 'routine'] ?? $priorityMap['routine'];
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-semibold text-teal-600">{{ $order->order_number }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $order->patient_name }}</p>
                            <p class="text-xs text-gray-400">{{ $order->patient_phone ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $order->tests_count ?? 0 }} test(s)
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold"
                                  style="background:{{ $s['bg'] }};color:{{ $s['color'] }};">{{ $s['label'] }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold"
                                  style="background:{{ $p['bg'] }};color:{{ $p['color'] }};">{{ $p['label'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $order->ordered_by_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y') : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                @if(in_array($order->status, ['pending', 'sample_collected', 'processing']))
                                <a href="{{ route('laboratory.result-entry', $order->id) }}"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Enter Results">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @endif
                                @if($order->status === 'completed')
                                <a href="#" class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors" title="View Report">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-500">No lab orders found</p>
                                <button @click="showNewOrderModal = true" class="text-sm font-semibold" style="color:#1447E6;">Create first order →</button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $orders->withQueryString()->links() }}
        </div>
        @endif
    </div>

    {{-- New Lab Order Modal --}}
    <div x-show="showNewOrderModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showNewOrderModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900">New Lab Order</h3>
                    <button @click="showNewOrderModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('laboratory.orders.store') }}" class="p-6 space-y-5">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Patient <span class="text-red-500">*</span></label>
                            <select name="patient_id" required
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select patient…</option>
                                @foreach($patients ?? [] as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->full_name }}@if($patient->phone) — {{ $patient->phone }}@endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                            <select name="priority"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="routine">Routine</option>
                                <option value="urgent">Urgent</option>
                                <option value="stat">STAT</option>
                            </select>
                        </div>
                    </div>

                    {{-- Test Selection --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Tests <span class="text-red-500">*</span></label>

                        {{-- Search tests --}}
                        <div class="relative mb-3">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" x-model="testSearch" placeholder="Search tests…"
                                class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="border border-gray-200 rounded-xl max-h-64 overflow-y-auto divide-y divide-gray-50">
                            @php
                                $testsByCategory = ($tests ?? collect())->groupBy('category');
                            @endphp
                            @foreach($testsByCategory as $category => $categoryTests)
                            <div x-show="filteredTests().some(t => {{ $categoryTests->pluck('id')->toJson() }}.includes(t))">
                                <div class="px-4 py-2 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $category }}</div>
                                @foreach($categoryTests as $test)
                                <label class="flex items-center justify-between px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors"
                                    x-show="testSearch === '' || '{{ strtolower($test->name) }}'.includes(testSearch.toLowerCase())">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" name="tests[]" value="{{ $test->id }}"
                                            class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $test->name }}</p>
                                            @if($test->sample_type ?? null)
                                            <p class="text-xs text-gray-400">{{ $test->sample_type }}@if($test->turnaround_hours) · {{ $test->turnaround_hours }}h TAT@endif</p>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold text-teal-600">₹{{ number_format($test->price ?? 0) }}</span>
                                </label>
                                @endforeach
                            </div>
                            @endforeach

                            @if(($tests ?? collect())->isEmpty())
                            <div class="px-4 py-8 text-center text-sm text-gray-400">
                                No tests in catalog.
                                <a href="{{ route('laboratory.catalog') }}" class="font-semibold" style="color:#1447E6;">Add tests →</a>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" rows="2"
                            placeholder="Any special instructions or clinical notes…"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showNewOrderModal = false"
                            class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
                        <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white rounded-xl" style="background:#1447E6;">
                            Create Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function labOrders() {
    return {
        showNewOrderModal: false,
        testSearch: '',
        filteredTests() {
            // returns all test ids when search is empty
            return [];
        }
    };
}
</script>
@endpush
@endsection
