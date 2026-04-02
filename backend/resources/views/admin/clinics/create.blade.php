@extends('admin.layouts.app')

@section('title', 'Create Clinic')
@section('subtitle', 'Onboard a new clinic to the platform')

@section('content')
<div class="max-w-3xl">
    <form action="{{ route('admin.clinics.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Clinic Information --}}
        <div class="bg-white rounded-xl p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Clinic Information</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Clinic Name *</label>
                    <input type="text" name="clinic_name" value="{{ old('clinic_name') }}" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Sharma Skin Clinic">
                    @error('clinic_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Specialty</label>
                    <select name="specialty" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select Specialty</option>
                        <option value="general">General Practice</option>
                        <option value="dermatology">Dermatology</option>
                        <option value="dental">Dental</option>
                        <option value="ophthalmology">Ophthalmology</option>
                        <option value="pediatrics">Pediatrics</option>
                        <option value="orthopedics">Orthopedics</option>
                        <option value="cardiology">Cardiology</option>
                        <option value="gynecology">Gynecology</option>
                        <option value="physiotherapy">Physiotherapy</option>
                        <option value="ent">ENT</option>
                        <option value="psychiatry">Psychiatry</option>
                        <option value="ayurveda">Ayurveda</option>
                        <option value="homeopathy">Homeopathy</option>
                        <option value="multi_specialty">Multi-Specialty</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Plan *</label>
                    <select name="plan" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="trial" {{ old('plan') === 'trial' ? 'selected' : '' }}>Trial (30 days free)</option>
                        <option value="solo" {{ old('plan') === 'solo' ? 'selected' : '' }}>Solo (₹999/month)</option>
                        <option value="small" {{ old('plan') === 'small' ? 'selected' : '' }}>Small (₹2,499/month)</option>
                        <option value="group" {{ old('plan') === 'group' ? 'selected' : '' }}>Group (₹4,999/month)</option>
                        <option value="enterprise" {{ old('plan') === 'enterprise' ? 'selected' : '' }}>Enterprise (Custom)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                    <input type="text" name="city" value="{{ old('city') }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Mumbai">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">State</label>
                    <input type="text" name="state" value="{{ old('state') }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Maharashtra">
                </div>

                <div id="trial-days-field" class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Trial Period (Days)</label>
                    <input type="number" name="trial_days" value="{{ old('trial_days', 30) }}" min="1" max="365"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
        </div>

        {{-- Hospital / HIMS Configuration --}}
        <div class="bg-white rounded-xl p-6 border border-gray-100" x-data="{
            facilityType: '{{ old('facility_type', 'clinic') }}',
            selectAll: false,
            himsFeatures: {
                @foreach(array_keys(config('hims_expansion.hims_feature_keys')) as $key)
                '{{ $key }}': {{ old('hims_features.' . $key) ? 'true' : 'false' }},
                @endforeach
            },
            toggleAll() {
                this.selectAll = !this.selectAll;
                Object.keys(this.himsFeatures).forEach(k => this.himsFeatures[k] = this.selectAll);
            }
        }">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Hospital / HIMS Configuration</h3>

            <div class="grid grid-cols-2 gap-4 mb-6">
                {{-- Facility Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Facility Type</label>
                    <select name="facility_type" x-model="facilityType"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach(config('hims_expansion.facility_types') as $value => $meta)
                            <option value="{{ $value }}" {{ old('facility_type', 'clinic') === $value ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                    @error('facility_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Licensed Beds --}}
                <div x-show="facilityType !== 'clinic'" x-transition>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Licensed Beds</label>
                    <input type="number" name="licensed_beds" min="0" value="{{ old('licensed_beds') }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="0">
                    @error('licensed_beds') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Subdomain --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Subdomain</label>
                <p class="text-sm text-gray-500">&lt;slug&gt;.clinic0s.com — auto-generated from clinic name</p>
            </div>

            {{-- HIMS Features --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-medium text-gray-700">HIMS Features</label>
                    <button type="button" @click="toggleAll()"
                        class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                        Select All Hospital Features
                    </button>
                </div>

                <div class="space-y-4">
                    {{-- Bed & Ward --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Bed & Ward</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach(['bed_management'] as $key)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="hims_features[{{ $key }}]" value="1"
                                    x-model="himsFeatures['{{ $key }}']"
                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ config('hims_expansion.hims_feature_keys.' . $key) }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- OPD --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">OPD</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach(['opd_hospital'] as $key)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="hims_features[{{ $key }}]" value="1"
                                    x-model="himsFeatures['{{ $key }}']"
                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ config('hims_expansion.hims_feature_keys.' . $key) }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- IPD --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">IPD</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach(['ipd', 'emergency'] as $key)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="hims_features[{{ $key }}]" value="1"
                                    x-model="himsFeatures['{{ $key }}']"
                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ config('hims_expansion.hims_feature_keys.' . $key) }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Pharmacy --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Pharmacy</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach(['pharmacy_inventory', 'pharmacy_ip_dispensing', 'pharmacy_op_dispensing', 'pharmacy_purchase_grn', 'pharmacy_returns'] as $key)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="hims_features[{{ $key }}]" value="1"
                                    x-model="himsFeatures['{{ $key }}']"
                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ config('hims_expansion.hims_feature_keys.' . $key) }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Lab / LIS --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Lab / LIS</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach(['lis_collection', 'lis_processing', 'lis_results', 'lis_reports_pdf', 'lis_hl7'] as $key)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="hims_features[{{ $key }}]" value="1"
                                    x-model="himsFeatures['{{ $key }}']"
                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ config('hims_expansion.hims_feature_keys.' . $key) }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Billing --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Billing</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach(['billing_unified', 'billing_insurance_extended', 'billing_credit_corporate', 'billing_gst_slabs', 'mis_revenue'] as $key)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="hims_features[{{ $key }}]" value="1"
                                    x-model="himsFeatures['{{ $key }}']"
                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ config('hims_expansion.hims_feature_keys.' . $key) }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Nursing --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Nursing</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach(['nursing_notes', 'mar', 'vitals_chart', 'nursing_care_plans', 'nursing_handover'] as $key)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="hims_features[{{ $key }}]" value="1"
                                    x-model="himsFeatures['{{ $key }}']"
                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ config('hims_expansion.hims_feature_keys.' . $key) }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Analytics --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Analytics</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach(['analytics_census', 'analytics_lab_tat', 'analytics_pharmacy_alerts', 'analytics_opd'] as $key)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="hims_features[{{ $key }}]" value="1"
                                    x-model="himsFeatures['{{ $key }}']"
                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ config('hims_expansion.hims_feature_keys.' . $key) }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('admin.clinics.partials.product-modules', ['enabledProductModuleKeys' => $enabledProductModuleKeys])

        {{-- Owner Information --}}
        <div class="bg-white rounded-xl p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Owner Account</h3>
            <p class="text-sm text-gray-500 mb-4">This person will have full admin access to the clinic.</p>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name *</label>
                    <input type="text" name="owner_name" value="{{ old('owner_name') }}" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Dr. Priya Sharma">
                    @error('owner_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email *</label>
                    <input type="email" name="owner_email" value="{{ old('owner_email') }}" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="doctor@clinic.com">
                    @error('owner_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone *</label>
                    <input type="text" name="owner_phone" value="{{ old('owner_phone') }}" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="+91 98765 43210">
                    @error('owner_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Password *</label>
                    <input type="password" name="owner_password" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="••••••••">
                    <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                    @error('owner_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.clinics.index') }}" class="px-6 py-2.5 text-gray-700 hover:text-gray-900">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors">
                Create Clinic
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const planSelect = document.querySelector('[name="plan"]');
    const trialDaysField = document.getElementById('trial-days-field');
    
    function toggleTrialDays() {
        trialDaysField.style.display = planSelect.value === 'trial' ? 'block' : 'none';
    }
    
    planSelect.addEventListener('change', toggleTrialDays);
    toggleTrialDays();
</script>
@endpush
@endsection
