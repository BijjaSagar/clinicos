@extends('layouts.app')

@section('title', 'Pharmacy Inventory')
@section('breadcrumb', 'Pharmacy Inventory')

@section('content')
<div x-data="inventoryPage()" class="p-4 sm:p-5 lg:p-7 space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 font-display">Pharmacy Inventory</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage medicines, stock levels & pricing</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('pharmacy.index') }}" class="px-3 py-2 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                Back
            </a>
            <button @click="showAddModal = true"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:shadow-lg"
                style="background:linear-gradient(135deg,#1447E6,#0891B2);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Medicine
            </button>
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

    {{-- Filter Bar --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <form method="GET" action="{{ route('pharmacy.inventory') }}" class="flex flex-col sm:flex-row gap-3">
            {{-- Search --}}
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search medicine name, generic name…"
                    class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            {{-- Category Filter --}}
            <select name="category" class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Categories</option>
                @foreach($categories ?? ['Antibiotics', 'Analgesics', 'Antacids', 'Vitamins', 'Cardiac', 'Diabetic', 'Others'] as $cat)
                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>

            {{-- Stock Status --}}
            <select name="stock_status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Stock</option>
                <option value="in-stock"    {{ request('stock_status') === 'in-stock'    ? 'selected' : '' }}>In Stock</option>
                <option value="low-stock"   {{ request('stock_status') === 'low-stock'   ? 'selected' : '' }}>Low Stock</option>
                <option value="out-of-stock"{{ request('stock_status') === 'out-of-stock'? 'selected' : '' }}>Out of Stock</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition-colors">
                Filter
            </button>
        </form>
    </div>

    {{-- Inventory Table --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">Medicine Catalog</h3>
            <span class="text-xs text-gray-400">{{ $items->total() ?? 0 }} items</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Medicine</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Current Stock</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Reorder Level</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nearest Expiry</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Price / Unit</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($items ?? [] as $item)
                    @php
                        $stockQty    = $item->current_stock ?? 0;
                        $reorderLvl  = $item->reorder_level ?? 0;
                        if ($stockQty <= 0) {
                            $stockStatus = ['label' => 'Out of Stock', 'bg' => '#fff1f2', 'color' => '#dc2626'];
                        } elseif ($stockQty <= $reorderLvl) {
                            $stockStatus = ['label' => 'Low Stock',    'bg' => '#fffbeb', 'color' => '#d97706'];
                        } else {
                            $stockStatus = ['label' => 'In Stock',     'bg' => '#ecfdf5', 'color' => '#059669'];
                        }
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $item->name ?? '—' }}</p>
                            @if($item->generic_name ?? null)
                            <p class="text-xs text-gray-400">{{ $item->generic_name }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $item->category ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-sm font-semibold text-gray-900">{{ $stockQty }}</span>
                            <span class="text-xs text-gray-400 ml-1">{{ $item->unit ?? '' }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $reorderLvl }} {{ $item->unit ?? '' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if($item->nearest_expiry ?? null)
                                @php
                                    $expiry = \Carbon\Carbon::parse($item->nearest_expiry);
                                    $expiring = $expiry->diffInDays(now()) <= 90 && $expiry->isFuture();
                                @endphp
                                <span class="{{ $expiring ? 'text-amber-600 font-semibold' : '' }}">
                                    {{ $expiry->format('M Y') }}
                                    @if($expiring) (soon)@endif
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">₹{{ number_format($item->price_per_unit ?? 0, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold"
                                  style="background:{{ $stockStatus['bg'] }};color:{{ $stockStatus['color'] }};">
                                {{ $stockStatus['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <button class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Add Stock"
                                    @click="openAddStock({{ $item->id }}, '{{ addslashes($item->name) }}')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                                <button class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-500">No medicines found</p>
                                <button @click="showAddModal = true" class="text-sm font-semibold" style="color:#1447E6;">Add first medicine →</button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($items) && $items->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $items->withQueryString()->links() }}
        </div>
        @endif
    </div>

    {{-- Add Medicine Modal --}}
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showAddModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900">Add New Medicine</h3>
                    <button @click="showAddModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('pharmacy.items.store') }}" class="p-6 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Medicine Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" required placeholder="e.g. Amoxicillin 500mg"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Generic Name</label>
                            <input type="text" name="generic_name" placeholder="e.g. Amoxicillin"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                            <select name="category" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select…</option>
                                @foreach(['Antibiotics', 'Analgesics', 'Antacids', 'Vitamins', 'Cardiac', 'Diabetic', 'Dermatology', 'Others'] as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit <span class="text-red-500">*</span></label>
                            <select name="unit" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select…</option>
                                <option value="Tablet">Tablet</option>
                                <option value="Capsule">Capsule</option>
                                <option value="Syrup (ml)">Syrup (ml)</option>
                                <option value="Injection">Injection</option>
                                <option value="Cream (gm)">Cream (gm)</option>
                                <option value="Drops">Drops</option>
                                <option value="Sachet">Sachet</option>
                                <option value="Strip">Strip</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Level</label>
                            <input type="number" name="reorder_level" min="0" placeholder="10"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price per Unit (₹) <span class="text-red-500">*</span></label>
                            <input type="number" name="price_per_unit" required min="0" step="0.01" placeholder="5.00"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">HSN Code</label>
                            <input type="text" name="hsn_code" placeholder="30049099"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">GST Rate (%)</label>
                            <select name="gst_rate" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="0">0%</option>
                                <option value="5" selected>5%</option>
                                <option value="12">12%</option>
                                <option value="18">18%</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="showAddModal = false"
                            class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
                        <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white rounded-xl" style="background:#1447E6;">
                            Add Medicine
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add Stock Modal --}}
    <div x-show="showStockModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showStockModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900">Add Stock — <span x-text="stockItem.name"></span></h3>
                    <button @click="showStockModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('pharmacy.stock.in') }}" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="pharmacy_item_id" x-bind:value="stockItem.id">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                            <input type="number" name="quantity" required min="1"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Batch No.</label>
                            <input type="text" name="batch_number" placeholder="e.g. BTH001"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                            <input type="date" name="expiry_date"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Price (₹)</label>
                            <input type="number" name="purchase_price" step="0.01" min="0"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                        <input type="text" name="supplier_name" placeholder="Supplier name"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showStockModal = false"
                            class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
                        <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white rounded-xl" style="background:#059669;">
                            Add Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function inventoryPage() {
    return {
        showAddModal: false,
        showStockModal: false,
        stockItem: { id: null, name: '' },
        openAddStock(id, name) {
            this.stockItem = { id, name };
            this.showStockModal = true;
        }
    };
}
</script>
@endpush
@endsection
