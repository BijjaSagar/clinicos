@extends('layouts.app')

@section('title', 'Add Medicine')
@section('breadcrumb', 'Add Medicine')

@section('content')
<div class="p-4 sm:p-5 lg:p-7 max-w-3xl mx-auto space-y-5">

    <div class="flex items-center gap-4">
        <a href="{{ route('pharmacy.inventory') }}" class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 font-display">Add Medicine to Inventory</h1>
            <p class="text-sm text-gray-500 mt-0.5">Add a new drug or medicine to the pharmacy catalog</p>
        </div>
    </div>

    @if($errors->any())
    <div class="px-4 py-3 rounded-xl text-sm" style="background:#fff1f2;color:#dc2626;border:1px solid #fecaca;">
        <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('pharmacy.items.store') }}" class="space-y-5">
        @csrf

        <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
            <h2 class="text-sm font-bold text-gray-900">Basic Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand / Trade Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g. Paracetamol 500mg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Generic Name</label>
                    <input type="text" name="generic_name" value="{{ old('generic_name') }}"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g. Acetaminophen">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit <span class="text-red-500">*</span></label>
                    <select name="unit" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="Tablets" {{ old('unit','Tablets')==='Tablets'?'selected':'' }}>Tablets</option>
                        <option value="Capsules" {{ old('unit')==='Capsules'?'selected':'' }}>Capsules</option>
                        <option value="ml" {{ old('unit')==='ml'?'selected':'' }}>ml</option>
                        <option value="mg" {{ old('unit')==='mg'?'selected':'' }}>mg</option>
                        <option value="Strips" {{ old('unit')==='Strips'?'selected':'' }}>Strips</option>
                        <option value="Vials" {{ old('unit')==='Vials'?'selected':'' }}>Vials</option>
                        <option value="Nos" {{ old('unit')==='Nos'?'selected':'' }}>Nos</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manufacturer</label>
                    <input type="text" name="manufacturer" value="{{ old('manufacturer') }}"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Schedule</label>
                    <select name="schedule" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">None / OTC</option>
                        <option value="H" {{ old('schedule')==='H'?'selected':'' }}>H (Hospital only)</option>
                        <option value="H1" {{ old('schedule')==='H1'?'selected':'' }}>H1 (Hospital only)</option>
                        <option value="G" {{ old('schedule')==='G'?'selected':'' }}>G (Rx)</option>
                        <option value="X" {{ old('schedule')==='X'?'selected':'' }}>X (Controlled)</option>
                        <option value="OTC" {{ old('schedule')==='OTC'?'selected':'' }}>OTC</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">HSN Code</label>
                    <input type="text" name="hsn_code" value="{{ old('hsn_code') }}"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g. 3004">
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
            <h2 class="text-sm font-bold text-gray-900">Pricing & GST</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">MRP (₹) <span class="text-red-500">*</span></label>
                    <input type="number" name="mrp" value="{{ old('mrp') }}" required min="0" step="0.01"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price (₹) <span class="text-red-500">*</span></label>
                    <input type="number" name="selling_price" value="{{ old('selling_price') }}" required min="0" step="0.01"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">GST Rate (%) <span class="text-red-500">*</span></label>
                    <select name="gst_rate" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="0" {{ old('gst_rate')==='0'?'selected':'' }}>0%</option>
                        <option value="5" {{ old('gst_rate')==='5'?'selected':'' }}>5%</option>
                        <option value="12" {{ old('gst_rate','12')==='12'?'selected':'' }}>12%</option>
                        <option value="18" {{ old('gst_rate')==='18'?'selected':'' }}>18%</option>
                        <option value="28" {{ old('gst_rate')==='28'?'selected':'' }}>28%</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
            <h2 class="text-sm font-bold text-gray-900">Stock Settings</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Level</label>
                    <input type="number" name="reorder_level" value="{{ old('reorder_level', 10) }}" min="0"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Alert when stock falls below this quantity</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Quantity</label>
                    <input type="number" name="reorder_qty" value="{{ old('reorder_qty', 100) }}" min="0"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Storage Conditions</label>
                    <input type="text" name="storage_conditions" value="{{ old('storage_conditions') }}"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g. Store below 25°C">
                </div>
                <div class="flex items-center gap-3 pt-5">
                    <input type="checkbox" name="is_controlled" id="is_controlled" value="1" {{ old('is_controlled') ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="is_controlled" class="text-sm font-medium text-gray-700">Controlled substance (requires prescription)</label>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl transition-all hover:shadow-lg"
                style="background:linear-gradient(135deg,#1447E6,#0891B2);">
                Add to Inventory
            </button>
            <a href="{{ route('pharmacy.inventory') }}" class="px-6 py-2.5 text-sm font-semibold text-gray-700 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
