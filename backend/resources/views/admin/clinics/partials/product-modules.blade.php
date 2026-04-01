{{--
  Super admin: enable product modules per clinic (stored in clinics.settings.enabled_product_modules).
  @var array<int, string> $enabledProductModuleKeys
--}}
@php
    $modules = config('clinic_modules.modules', []);
    $specialtyPresets = config('clinic_modules.specialty_suggestions', []);
    $enabledSet = array_flip($enabledProductModuleKeys ?? []);
@endphp
<div class="bg-white rounded-xl p-6 border border-gray-100" id="product-modules-panel">
    <h3 class="text-lg font-semibold text-gray-900 mb-1">Product modules</h3>
    <p class="text-sm text-gray-500 mb-4">
        Only checked modules appear in the clinic sidebar (role rules still apply). Leave specialty-driven suggestions or tune manually for the region you sell into.
    </p>

    <div class="flex flex-wrap gap-2 mb-4">
        <button type="button" id="pm-select-all" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200">Select all</button>
        <button type="button" id="pm-clear-all" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200">Clear all</button>
        <button type="button" id="pm-apply-specialty" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100">Apply specialty preset</button>
    </div>

    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Global</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
        @foreach($modules as $key => $meta)
            @if(($meta['region'] ?? 'global') === 'IN')
                @continue
            @endif
            <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-100 hover:border-indigo-200 cursor-pointer">
                <input type="checkbox" name="product_modules[]" value="{{ $key }}"
                    class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 pm-mod-cb"
                    {{ isset($enabledSet[$key]) ? 'checked' : '' }}>
                <span>
                    <span class="text-sm font-medium text-gray-900">{{ $meta['label'] ?? $key }}</span>
                    @if(!empty($meta['description']))
                        <span class="block text-xs text-gray-500 mt-0.5">{{ $meta['description'] }}</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>

    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 mb-2">India-specific</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-2">
        @foreach($modules as $key => $meta)
            @if(($meta['region'] ?? '') !== 'IN')
                @continue
            @endif
            <label class="flex items-start gap-3 p-3 rounded-xl border border-amber-100 bg-amber-50/40 hover:border-amber-200 cursor-pointer">
                <input type="checkbox" name="product_modules[]" value="{{ $key }}"
                    class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 pm-mod-cb"
                    {{ isset($enabledSet[$key]) ? 'checked' : '' }}>
                <span>
                    <span class="text-sm font-medium text-gray-900">{{ $meta['label'] ?? $key }}</span>
                    @if(!empty($meta['description']))
                        <span class="block text-xs text-gray-500 mt-0.5">{{ $meta['description'] }}</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
    @error('product_modules')
        <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
    @enderror
    @error('product_modules.*')
        <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
    @enderror
</div>

@push('scripts')
<script>
(function () {
    const specialtySelect = document.querySelector('select[name="specialty"]');
    const presets = @json($specialtyPresets);
    const boxes = () => Array.from(document.querySelectorAll('.pm-mod-cb'));

    document.getElementById('pm-select-all')?.addEventListener('click', function () {
        boxes().forEach(function (b) { b.checked = true; });
        console.log('[product-modules] select all');
    });
    document.getElementById('pm-clear-all')?.addEventListener('click', function () {
        boxes().forEach(function (b) { b.checked = false; });
        console.log('[product-modules] clear all');
    });
    document.getElementById('pm-apply-specialty')?.addEventListener('click', function () {
        const spec = specialtySelect?.value || '';
        const suggested = presets[spec];
        console.log('[product-modules] apply preset', spec, suggested);
        if (!suggested || !Array.isArray(suggested)) {
            boxes().forEach(function (b) { b.checked = true; });
            return;
        }
        const set = new Set(suggested);
        boxes().forEach(function (b) { b.checked = set.has(b.value); });
    });
})();
</script>
@endpush
