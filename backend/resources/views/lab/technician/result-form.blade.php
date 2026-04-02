@extends('layouts.app')

@section('title', 'Enter Lab Results — ' . $order->order_number)

@section('content')
<div
    x-data="{
        hasCritical: false,
        results: @js($items->map(fn($i) => ['item_id' => $i->id, 'value' => $i->result_value ?? '', 'is_abnormal' => (bool)$i->is_abnormal, 'is_critical' => false, 'remarks' => $i->remarks ?? ''])),
        checkCritical() {
            this.hasCritical = this.results.some(r => r.is_critical);
        },
        submitForm() {
            if (this.hasCritical) {
                if (!confirm('WARNING: One or more results are marked CRITICAL. Confirm submission to notify the doctor immediately.')) return;
            }
            this.$refs.resultForm.submit();
        }
    }"
>

    {{-- Back + Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('lab.technician.dashboard') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Work Queue
        </a>
    </div>

    {{-- Patient / Order Info Card --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Patient</p>
                <p class="text-xl font-bold text-gray-900">{{ $order->patient_name }}</p>
                <p class="text-sm text-gray-500 mt-0.5">
                    @if($order->date_of_birth)
                        {{ \Carbon\Carbon::parse($order->date_of_birth)->age }} years
                        &middot;
                    @endif
                    {{ ucfirst($order->gender ?? '—') }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 font-mono">{{ $order->order_number }}</p>
                <p class="text-sm text-gray-700 mt-1">Ordered by: <span class="font-medium">Dr. {{ $order->doctor_name }}</span></p>
                <div class="mt-2">
                    @if($order->priority === 'stat')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700 uppercase">STAT</span>
                    @elseif($order->priority === 'urgent')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-orange-100 text-orange-700 uppercase">URGENT</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 uppercase">ROUTINE</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Critical Warning Banner --}}
    <div x-show="hasCritical" x-transition class="mb-4 rounded-lg bg-red-50 border border-red-300 p-4 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p class="text-sm font-semibold text-red-800">Critical values detected — the doctor will be notified immediately upon saving.</p>
    </div>

    {{-- Results Form --}}
    <form x-ref="resultForm" action="{{ route('lab.technician.save-results', $order->id) }}" method="POST">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-800">Test Results</h2>
                <p class="text-xs text-gray-500 mt-0.5">Enter values for each test. Check Abnormal or Critical as appropriate.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference Range</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result Value</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Abnormal</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Critical</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($items as $index => $item)
                            <tr
                                x-data="{}"
                                :class="{
                                    'bg-red-50': results[{{ $index }}].is_critical,
                                    'bg-yellow-50': results[{{ $index }}].is_abnormal && !results[{{ $index }}].is_critical
                                }"
                                class="transition-colors"
                            >
                                <td class="px-4 py-3">
                                    <input type="hidden" name="results[{{ $index }}][item_id]" value="{{ $item->id }}">
                                    <p class="text-sm font-medium text-gray-900">{{ $item->test_name }}</p>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $item->category ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600 font-mono">{{ $item->reference_range ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <input
                                        type="text"
                                        name="results[{{ $index }}][value]"
                                        x-model="results[{{ $index }}].value"
                                        placeholder="Enter value"
                                        class="w-32 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        :class="{
                                            'border-red-500 bg-red-50': results[{{ $index }}].is_critical,
                                            'border-yellow-500': results[{{ $index }}].is_abnormal && !results[{{ $index }}].is_critical
                                        }"
                                        required
                                    >
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $item->unit ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        name="results[{{ $index }}][is_abnormal]"
                                        x-model="results[{{ $index }}].is_abnormal"
                                        value="1"
                                        class="h-4 w-4 rounded border-gray-300 text-yellow-500 focus:ring-yellow-500 cursor-pointer"
                                    >
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        name="results[{{ $index }}][is_critical]"
                                        x-model="results[{{ $index }}].is_critical"
                                        @change="checkCritical(); if(results[{{ $index }}].is_critical) results[{{ $index }}].is_abnormal = true"
                                        value="1"
                                        class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-600 cursor-pointer"
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="text"
                                        name="results[{{ $index }}][remarks]"
                                        x-model="results[{{ $index }}].remarks"
                                        placeholder="Optional remarks"
                                        class="w-40 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('lab.technician.dashboard') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button
                type="button"
                @click="submitForm()"
                class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors shadow-sm"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save &amp; Notify Doctor
            </button>
        </div>
    </form>

</div>
@endsection
