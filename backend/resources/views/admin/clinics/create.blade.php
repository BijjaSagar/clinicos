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
