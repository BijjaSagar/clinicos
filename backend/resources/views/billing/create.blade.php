@extends('layouts.app')

@section('title', 'New Invoice')
@section('breadcrumb', 'Create Invoice')

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900">Create Invoice</h1>
            <p class="text-sm text-gray-500 mt-0.5">Generate a new invoice for a patient</p>
        </div>

        {{-- Debug: Show if visit_id is being passed --}}
        @if(request('visit_id'))
        <div class="mb-4 p-3 bg-green-100 border border-green-300 rounded-lg text-sm text-green-800">
            ✅ Linking to Visit #{{ request('visit_id') }}
        </div>
        @else
        <div class="mb-4 p-3 bg-yellow-100 border border-yellow-300 rounded-lg text-sm text-yellow-800">
            ⚠️ No visit linked - invoice will not appear in EMR Billing tab
        </div>
        @endif

        <form action="{{ route('billing.store') }}" method="POST" x-data="invoiceForm()" class="space-y-6">
            @csrf
            
            {{-- Visit ID (hidden, passed from EMR) --}}
            <input type="hidden" name="visit_id" value="{{ request('visit_id') }}">

            {{-- Patient Selection --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="font-bold text-gray-900">Patient</h3>
                </div>
                <div class="p-5">
                    <select name="patient_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select patient</option>
                        @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                            {{ $patient->name }} — {{ $patient->phone }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900">Invoice Items</h3>
                </div>
                <div class="p-5">
                    <table class="w-full">
                        <thead class="text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="text-left pb-3">Description</th>
                                <th class="text-left pb-3">SAC Code</th>
                                <th class="text-right pb-3 w-32">Amount (₹)</th>
                                <th class="w-16"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="border-t border-gray-100">
                                    <td class="py-3 pr-3">
                                        <input 
                                            type="text" 
                                            :name="`items[${index}][description]`"
                                            x-model="item.description"
                                            placeholder="Service description"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            required
                                        >
                                    </td>
                                    <td class="py-3 pr-3">
                                        <input 
                                            type="text" 
                                            :name="`items[${index}][sac_code]`"
                                            x-model="item.sac_code"
                                            placeholder="998314"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        >
                                    </td>
                                    <td class="py-3 pr-3">
                                        <input 
                                            type="number" 
                                            :name="`items[${index}][amount]`"
                                            x-model.number="item.amount"
                                            @input="calculateTotals()"
                                            placeholder="0"
                                            min="0"
                                            step="0.01"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm text-right"
                                            required
                                        >
                                    </td>
                                    <td class="py-3 text-center">
                                        <button 
                                            type="button" 
                                            @click="removeItem(index)"
                                            x-show="items.length > 1"
                                            class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <button 
                        type="button" 
                        @click="addItem()"
                        class="mt-4 flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Line Item
                    </button>
                </div>
            </div>

            {{-- Summary --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="font-bold text-gray-900">Summary</h3>
                </div>
                <div class="p-5">
                    <div class="max-w-sm ml-auto space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-semibold text-gray-900">₹<span x-text="subtotal.toFixed(2)">0.00</span></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">CGST (9%)</span>
                            <span class="font-semibold text-gray-900">₹<span x-text="cgst.toFixed(2)">0.00</span></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">SGST (9%)</span>
                            <span class="font-semibold text-gray-900">₹<span x-text="sgst.toFixed(2)">0.00</span></span>
                        </div>
                        <div class="border-t border-gray-200 pt-3 flex justify-between">
                            <span class="font-bold text-gray-900">Total</span>
                            <span class="font-bold text-xl text-blue-600">₹<span x-text="total.toFixed(2)">0.00</span></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('billing.index') }}" class="px-6 py-2.5 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Create Invoice
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function invoiceForm() {
    return {
        items: [{ description: '', sac_code: '', amount: 0 }],
        subtotal: 0,
        cgst: 0,
        sgst: 0,
        total: 0,

        addItem() {
            this.items.push({ description: '', sac_code: '', amount: 0 });
        },

        removeItem(index) {
            this.items.splice(index, 1);
            this.calculateTotals();
        },

        calculateTotals() {
            this.subtotal = this.items.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
            this.cgst = this.subtotal * 0.09;
            this.sgst = this.subtotal * 0.09;
            this.total = this.subtotal + this.cgst + this.sgst;
        }
    };
}
</script>
@endpush
@endsection
