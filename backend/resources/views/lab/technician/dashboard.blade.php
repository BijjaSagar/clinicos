@extends('layouts.app')

@section('title', 'Lab — Work Queue')

@section('content')
<div x-data="{ collectModal: false, activeOrderId: null, sampleType: '', collectionNotes: '' }">

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Lab — Work Queue</h1>
            <p class="text-sm text-gray-500 mt-1">Manage pending test orders and enter results</p>
        </div>
        <div class="text-sm text-gray-400">Auto-refreshes every 60 seconds</div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-red-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-red-500 uppercase tracking-wide">Pending</p>
            <p class="text-3xl font-bold text-red-600 mt-1">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-yellow-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-yellow-600 uppercase tracking-wide">Sample Collected</p>
            <p class="text-3xl font-bold text-yellow-500 mt-1">{{ $stats['sample_collected'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-blue-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-blue-500 uppercase tracking-wide">Processing</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['processing'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-green-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-green-600 uppercase tracking-wide">Completed Today</p>
            <p class="text-3xl font-bold text-green-600 mt-1">{{ $stats['completed_today'] }}</p>
        </div>
    </div>

    {{-- Priority Queue Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-800">Priority Work Queue</h2>
        </div>

        @if($pendingOrders->isEmpty())
            <div class="text-center py-16 text-gray-400">
                <svg class="mx-auto h-12 w-12 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="font-medium">No pending orders</p>
                <p class="text-sm mt-1">All tests are up to date.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordering Doctor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tests</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordered At</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($pendingOrders as $order)
                            @php
                                $testCount = \Illuminate\Support\Facades\DB::table('lab_order_items')
                                    ->where('lab_order_id', $order->id)->count();
                                $age = $order->date_of_birth
                                    ? \Carbon\Carbon::parse($order->date_of_birth)->age . 'y'
                                    : '—';
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900">
                                    {{ $order->order_number }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($order->priority === 'stat')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700 uppercase">STAT</span>
                                    @elseif($order->priority === 'urgent')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-orange-100 text-orange-700 uppercase">URGENT</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 uppercase">ROUTINE</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $order->patient_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $age }} &middot; {{ ucfirst($order->gender ?? '—') }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    Dr. {{ $order->doctor_name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 font-semibold text-xs">
                                        {{ $testCount }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($order->created_at)->format('d M, H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($order->status === 'pending')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Pending</span>
                                    @elseif($order->status === 'sample_collected')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Sample Collected</span>
                                    @elseif($order->status === 'processing')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Processing</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Completed</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if($order->status === 'pending')
                                            <button
                                                @click="collectModal = true; activeOrderId = {{ $order->id }}"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-yellow-500 text-white hover:bg-yellow-600 transition-colors"
                                            >
                                                Collect Sample
                                            </button>
                                        @elseif(in_array($order->status, ['sample_collected', 'processing']))
                                            <a
                                                href="{{ route('lab.technician.result-form', $order->id) }}"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors"
                                            >
                                                Enter Results
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Sample Collection Modal --}}
    <div
        x-show="collectModal"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
        style="display: none;"
    >
        <div
            x-show="collectModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.outside="collectModal = false"
            class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6"
        >
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-semibold text-gray-900">Collect Sample</h3>
                <button @click="collectModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sample Type <span class="text-red-500">*</span></label>
                    <select x-model="sampleType" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Select sample type —</option>
                        <option value="blood_venous">Blood (Venous)</option>
                        <option value="blood_capillary">Blood (Capillary)</option>
                        <option value="urine">Urine</option>
                        <option value="stool">Stool</option>
                        <option value="sputum">Sputum</option>
                        <option value="swab">Swab</option>
                        <option value="csf">CSF</option>
                        <option value="tissue">Tissue / Biopsy</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Collection Notes</label>
                    <textarea
                        x-model="collectionNotes"
                        rows="3"
                        placeholder="Optional notes about sample collection..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    ></textarea>
                </div>
            </div>

            <div class="mt-6 flex gap-3 justify-end">
                <button
                    @click="collectModal = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                >
                    Cancel
                </button>
                <button
                    @click="submitCollect(activeOrderId, sampleType, collectionNotes)"
                    class="px-4 py-2 text-sm font-medium text-white bg-yellow-500 rounded-lg hover:bg-yellow-600 transition-colors"
                >
                    Confirm Collection
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Auto-refresh every 60 seconds
    setTimeout(() => location.reload(), 60000);

    function submitCollect(orderId, sampleType, notes) {
        if (!sampleType) {
            alert('Please select a sample type.');
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        fetch(`/lab-portal/${orderId}/collect`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                sample_type: sampleType,
                collection_notes: notes,
            }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'An error occurred.');
            }
        })
        .catch(() => alert('Failed to submit. Please try again.'));
    }
</script>
@endpush
