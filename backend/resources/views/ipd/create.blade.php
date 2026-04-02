@extends('layouts.app')

@section('title', 'Admit Patient')
@section('breadcrumb', 'Admit Patient')

@section('content')
<div x-data="admitForm()" class="p-4 sm:p-5 lg:p-7 max-w-3xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('ipd.index') }}"
           class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 font-display">Admit Patient</h1>
            <p class="text-sm text-gray-500 mt-0.5">Fill in the admission details below</p>
        </div>
    </div>

    @if($errors->any())
    <div class="px-4 py-3 rounded-xl text-sm" style="background:#fff1f2;color:#dc2626;border:1px solid #fecaca;">
        <p class="font-semibold mb-1">Please fix the following errors:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('ipd.store') }}" class="space-y-5">
        @csrf

        {{-- Patient --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
            <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">1</span>
                Patient Information
            </h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Patient <span class="text-red-500">*</span></label>
                <select name="patient_id" required
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">Select patient…</option>
                    @foreach($patients as $patient)
                    <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                        {{ $patient->name }}{{ $patient->phone ? ' — '.$patient->phone : '' }}{{ $patient->age_years ? ' ('.$patient->age_years.'y'.($patient->sex ? ', '.ucfirst($patient->sex) : '').')' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Bed Assignment --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
            <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">2</span>
                Ward &amp; Bed Assignment
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ward <span class="text-red-500">*</span></label>
                    <select name="ward_id" x-model="selectedWard" @change="filterBeds()"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select ward…</option>
                        @foreach($wards as $ward)
                        <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bed <span class="text-red-500">*</span></label>
                    <select name="bed_id" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select bed…</option>
                        @foreach($availableBeds as $wardName => $beds)
                        <optgroup label="{{ $wardName }}">
                            @foreach($beds as $bed)
                            <option value="{{ $bed->id }}"
                                data-ward="{{ $bed->ward_id }}"
                                {{ old('bed_id') == $bed->id ? 'selected' : '' }}>
                                Bed {{ $bed->bed_number }}@if($bed->bed_type) ({{ ucfirst($bed->bed_type) }})@endif
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Only available beds are shown</p>
                </div>
            </div>
        </div>

        {{-- Admission Details --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
            <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">3</span>
                Admission Details
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Primary Doctor <span class="text-red-500">*</span></label>
                    <select name="primary_doctor_id" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select doctor…</option>
                        @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}" {{ old('primary_doctor_id') == $doctor->id ? 'selected' : '' }}>
                            {{ $doctor->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Admission Type <span class="text-red-500">*</span></label>
                    <select name="admission_type" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select type…</option>
                        <option value="emergency" {{ old('admission_type') === 'emergency' ? 'selected' : '' }}>Emergency</option>
                        <option value="planned"   {{ old('admission_type') === 'planned'   ? 'selected' : '' }}>Planned</option>
                        <option value="transfer"  {{ old('admission_type') === 'transfer'  ? 'selected' : '' }}>Transfer</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Admission Diagnosis <span class="text-red-500">*</span></label>
                <input type="text" name="diagnosis_at_admission" required
                    value="{{ old('diagnosis_at_admission') }}"
                    placeholder="e.g. Acute Appendicitis, Type 2 Diabetes Mellitus…"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Chief Complaint</label>
                <textarea name="chief_complaint" rows="3"
                    placeholder="Patient's main presenting complaint…"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('chief_complaint') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Diet Type</label>
                    <select name="diet_type"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select diet…</option>
                        <option value="normal"     {{ old('diet_type') === 'normal'     ? 'selected' : '' }}>Normal</option>
                        <option value="liquid"     {{ old('diet_type') === 'liquid'     ? 'selected' : '' }}>Liquid</option>
                        <option value="soft"       {{ old('diet_type') === 'soft'       ? 'selected' : '' }}>Soft</option>
                        <option value="diabetic"   {{ old('diet_type') === 'diabetic'   ? 'selected' : '' }}>Diabetic</option>
                        <option value="npo"        {{ old('diet_type') === 'npo'        ? 'selected' : '' }}>NPO (Nil per Oral)</option>
                        <option value="low_sodium" {{ old('diet_type') === 'low_sodium' ? 'selected' : '' }}>Low Sodium</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Stay (days)</label>
                    <input type="number" name="estimated_days" min="1" max="365"
                        value="{{ old('estimated_days') }}"
                        placeholder="e.g. 5"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                <textarea name="notes" rows="3"
                    placeholder="Any additional notes or instructions…"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between pt-1">
            <a href="{{ route('ipd.index') }}" class="px-4 py-2.5 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl transition-all hover:shadow-lg hover:scale-[1.02]"
                style="background:linear-gradient(135deg,#1447E6,#0891B2);">
                Admit Patient
            </button>
        </div>

    </form>
</div>

@push('scripts')
<script>
function admitForm() {
    return {
        selectedWard: '',
        filterBeds() {
            const wardId = this.selectedWard;
            const bedSelect = document.querySelector('select[name="bed_id"]');
            if (!bedSelect) return;
            Array.from(bedSelect.options).forEach(opt => {
                if (!opt.value) return;
                const optWard = opt.getAttribute('data-ward');
                opt.hidden = wardId && optWard !== wardId;
            });
            bedSelect.value = '';
        }
    };
}
</script>
@endpush
@endsection
