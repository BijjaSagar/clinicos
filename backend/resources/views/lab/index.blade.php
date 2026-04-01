@extends('layouts.app')

@section('title', 'Lab Integration')

@section('breadcrumb', 'Lab Orders')

@section('content')
<div x-data="labDashboard()" class="p-6 space-y-6">
    @if(isset($labSchemaReady) && !$labSchemaReady)
    <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
        Lab orders table is missing. Run <code class="bg-amber-100 px-1 rounded">php artisan migrate</code> to enable this module.
    </div>
    @endif
    {{-- Header --}}
    <div class="bg-gradient-to-r from-teal-500 to-cyan-600 rounded-2xl p-6 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold">Lab Integration</h1>
                <p class="text-white/80 mt-1">Order tests from major diagnostic labs</p>
            </div>
            <button @click="showOrderModal = true" class="ml-auto px-6 py-3 bg-white text-teal-600 font-semibold rounded-xl hover:bg-teal-50 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Lab Order
            </button>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-2xl">📝</div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['orders_today'] }}</div>
                    <div class="text-sm text-gray-500">Orders Today</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-2xl">⏳</div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['pending_results'] }}</div>
                    <div class="text-sm text-gray-500">Awaiting Results</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center text-2xl">✅</div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['results_received'] }}</div>
                    <div class="text-sm text-gray-500">Results This Month</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lab Providers --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50">
            <h2 class="font-semibold text-gray-900">Connected Lab Providers</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @php
                    $labs = [
                        ['name' => 'Dr. Lal PathLabs', 'code' => 'lal_pathlabs', 'color' => 'bg-red-500'],
                        ['name' => 'SRL Diagnostics', 'code' => 'srl', 'color' => 'bg-blue-600'],
                        ['name' => 'Thyrocare', 'code' => 'thyrocare', 'color' => 'bg-purple-600'],
                        ['name' => 'Metropolis', 'code' => 'metropolis', 'color' => 'bg-green-600'],
                        ['name' => 'Pathkind Labs', 'code' => 'pathkind', 'color' => 'bg-orange-500'],
                    ];
                @endphp
                @foreach($labs as $lab)
                <div class="border border-gray-200 rounded-lg p-4 text-center hover:border-teal-300 transition-colors cursor-pointer" @click="selectProvider('{{ $lab['code'] }}')">
                    <div class="w-12 h-12 mx-auto rounded-lg {{ $lab['color'] }} flex items-center justify-center text-white font-bold text-sm mb-2">
                        {{ substr($lab['name'], 0, 2) }}
                    </div>
                    <div class="text-sm font-medium text-gray-900">{{ $lab['name'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 class="font-semibold text-gray-900">Recent Lab Orders</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Patient</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Lab</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tests</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentOrders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <span class="font-mono text-sm font-medium text-teal-600">{{ $order->order_number }}</span>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $order->patient_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $order->provider_name }}</td>
                        <td class="px-4 py-3">
                            @php $tests = json_decode($order->tests, true) ?? []; @endphp
                            <span class="text-sm text-gray-600">{{ count($tests) }} tests</span>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900">₹{{ number_format($order->total_amount) }}</td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-gray-100 text-gray-700',
                                    'sample_collected' => 'bg-blue-100 text-blue-700',
                                    'processing' => 'bg-yellow-100 text-yellow-700',
                                    'completed' => 'bg-green-100 text-green-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100' }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                @if($order->result_url)
                                <a href="/lab/orders/{{ $order->id }}/download" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Download Result">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </a>
                                @endif
                                <button class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                            No lab orders yet. Create your first order.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- New Order Modal --}}
    <div x-show="showOrderModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showOrderModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Create Lab Order</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Patient</label>
                            <select x-model="orderForm.patient_id" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                                <option value="">Select patient</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lab Provider</label>
                            <select x-model="orderForm.provider" @change="loadTests()" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                                <option value="">Select lab</option>
                                <option value="lal_pathlabs">Dr. Lal PathLabs</option>
                                <option value="srl">SRL Diagnostics</option>
                                <option value="thyrocare">Thyrocare</option>
                                <option value="metropolis">Metropolis</option>
                                <option value="pathkind">Pathkind Labs</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search Tests</label>
                        <input type="text" x-model="testSearch" @input="searchTests()" placeholder="Search tests..." class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                    </div>

                    <div x-show="availableTests.length" class="border border-gray-200 rounded-lg max-h-48 overflow-y-auto">
                        <template x-for="test in availableTests" :key="test.code">
                            <div class="flex items-center justify-between px-4 py-2 hover:bg-gray-50 cursor-pointer" @click="addTest(test)">
                                <div>
                                    <div class="font-medium text-sm" x-text="test.name"></div>
                                    <div class="text-xs text-gray-500" x-text="test.code + ' • ' + test.category"></div>
                                </div>
                                <div class="text-sm font-semibold text-teal-600" x-text="'₹' + test.price"></div>
                            </div>
                        </template>
                    </div>

                    <div x-show="orderForm.tests.length">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Selected Tests</label>
                        <div class="space-y-2">
                            <template x-for="(test, idx) in orderForm.tests" :key="idx">
                                <div class="flex items-center justify-between px-3 py-2 bg-teal-50 rounded-lg">
                                    <span class="text-sm font-medium" x-text="test.name"></span>
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-teal-600" x-text="'₹' + test.price"></span>
                                        <button @click="removeTest(idx)" class="text-red-500 hover:text-red-600">×</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="mt-2 text-right font-semibold text-gray-900" x-text="'Total: ₹' + orderTotal"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sample Collection</label>
                            <select x-model="orderForm.sample_collection_type" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                                <option value="lab">At Lab</option>
                                <option value="home">Home Collection</option>
                            </select>
                        </div>
                        <div x-show="orderForm.sample_collection_type === 'home'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Collection Date</label>
                            <input type="date" x-model="orderForm.collection_date" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                        </div>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-100 flex justify-end gap-3">
                    <button @click="showOrderModal = false" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50">Cancel</button>
                    <button @click="submitOrder()" :disabled="!canSubmit" class="px-4 py-2 bg-teal-600 text-white font-medium rounded-lg hover:bg-teal-700 disabled:opacity-50">
                        Create Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
console.log('Lab Integration dashboard loaded');

function labDashboard() {
    return {
        showOrderModal: false,
        testSearch: '',
        availableTests: [],
        orderForm: {
            patient_id: '',
            provider: '',
            tests: [],
            sample_collection_type: 'lab',
            collection_date: '',
        },

        get orderTotal() {
            return this.orderForm.tests.reduce((sum, t) => sum + t.price, 0);
        },

        get canSubmit() {
            return this.orderForm.patient_id && this.orderForm.provider && this.orderForm.tests.length > 0;
        },

        selectProvider(provider) {
            this.orderForm.provider = provider;
            this.showOrderModal = true;
            this.loadTests();
        },

        async loadTests() {
            if (!this.orderForm.provider) return;

            const response = await fetch('/lab/tests/' + this.orderForm.provider);
            const data = await response.json();
            if (data.success) {
                this.availableTests = data.tests;
            }
        },

        async searchTests() {
            if (!this.orderForm.provider) return;

            const response = await fetch('/lab/tests/' + this.orderForm.provider + '?search=' + this.testSearch);
            const data = await response.json();
            if (data.success) {
                this.availableTests = data.tests;
            }
        },

        addTest(test) {
            if (!this.orderForm.tests.find(t => t.code === test.code)) {
                this.orderForm.tests.push(test);
            }
        },

        removeTest(idx) {
            this.orderForm.tests.splice(idx, 1);
        },

        async submitOrder() {
            try {
                const response = await fetch('/lab/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.orderForm),
                });

                const data = await response.json();

                if (data.success) {
                    alert('Order created: ' + data.order_number);
                    this.showOrderModal = false;
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Order error:', error);
                alert('Failed to create order');
            }
        },
    };
}
</script>
@endsection
