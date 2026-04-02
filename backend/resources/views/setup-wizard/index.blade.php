<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ClinicOS — Setup</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex flex-col items-center justify-center p-6" x-data="setupWizard()">
        <!-- Logo -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-blue-800">ClinicOS</h1>
            <p class="text-gray-500 mt-1">Let's set up your clinic in just a few steps</p>
        </div>

        <!-- Progress bar -->
        <div class="w-full max-w-2xl mb-8">
            <div class="flex items-center justify-between mb-2">
                <template x-for="(s, i) in steps" :key="i">
                    <div class="flex items-center" :class="i < steps.length - 1 ? 'flex-1' : ''">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all"
                             :class="i <= currentStep ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500'">
                            <span x-text="i + 1"></span>
                        </div>
                        <div x-show="i < steps.length - 1" class="flex-1 h-1 mx-2 rounded transition-all"
                             :class="i < currentStep ? 'bg-blue-600' : 'bg-gray-200'"></div>
                    </div>
                </template>
            </div>
            <div class="flex justify-between text-xs text-gray-500">
                <template x-for="(s, i) in steps" :key="'label-'+i">
                    <span x-text="s.label" class="text-center" :class="i <= currentStep ? 'text-blue-700 font-semibold' : ''"></span>
                </template>
            </div>
        </div>

        <!-- Card -->
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl p-8">
            <!-- Step 1: Clinic Info -->
            <div x-show="currentStep === 0" x-transition>
                <h2 class="text-xl font-bold text-gray-800 mb-1">Clinic Information</h2>
                <p class="text-sm text-gray-500 mb-6">Basic details about your clinic or hospital</p>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Clinic / Hospital Name *</label>
                        <input type="text" x-model="data.name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" x-model="data.phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" x-model="data.email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <input type="text" x-model="data.address_line1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                        <input type="text" x-model="data.city" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                        <input type="text" x-model="data.state" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                        <input type="text" x-model="data.pincode" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">GSTIN</label>
                        <input type="text" x-model="data.gstin" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Step 2: Specialties -->
            <div x-show="currentStep === 1" x-transition>
                <h2 class="text-xl font-bold text-gray-800 mb-1">Select Specialties</h2>
                <p class="text-sm text-gray-500 mb-6">Choose the specialties your clinic offers</p>
                <div class="grid grid-cols-3 gap-3">
                    @php
                    $specialties = ['Dermatology', 'Physiotherapy', 'Dental', 'Ophthalmology', 'Orthopaedics', 'ENT', 'Gynaecology', 'General Practice', 'Paediatrics', 'Cardiology', 'Neurology', 'Urology', 'Psychiatry', 'Oncology', 'Pulmonology'];
                    @endphp
                    @foreach($specialties as $s)
                    <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition-colors"
                           :class="data.specialties.includes('{{ $s }}') ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                        <input type="checkbox" value="{{ $s }}" x-model="data.specialties" class="rounded text-blue-600">
                        <span class="text-sm">{{ $s }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Step 3: Facility Type -->
            <div x-show="currentStep === 2" x-transition>
                <h2 class="text-xl font-bold text-gray-800 mb-1">Facility Type</h2>
                <p class="text-sm text-gray-500 mb-6">Help us configure the right modules for you</p>
                <div class="space-y-4">
                    <label class="block p-4 border-2 rounded-xl cursor-pointer transition-all"
                           :class="data.facility_type === 'clinic' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" x-model="data.facility_type" value="clinic" class="hidden">
                        <div class="font-semibold text-gray-800">Clinic (Outpatient)</div>
                        <div class="text-sm text-gray-500 mt-1">Small practice, no inpatient beds. Appointments, EMR, billing, prescriptions.</div>
                    </label>
                    <label class="block p-4 border-2 rounded-xl cursor-pointer transition-all"
                           :class="data.facility_type === 'hospital' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" x-model="data.facility_type" value="hospital" class="hidden">
                        <div class="font-semibold text-gray-800">Hospital</div>
                        <div class="text-sm text-gray-500 mt-1">Inpatient beds, wards, IPD, pharmacy, lab. 50+ beds supported.</div>
                    </label>
                    <label class="block p-4 border-2 rounded-xl cursor-pointer transition-all"
                           :class="data.facility_type === 'multispecialty_hospital' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" x-model="data.facility_type" value="multispecialty_hospital" class="hidden">
                        <div class="font-semibold text-gray-800">Multispecialty Hospital</div>
                        <div class="text-sm text-gray-500 mt-1">Large hospital with multiple departments, advanced modules, full HIMS.</div>
                    </label>
                    <div x-show="data.facility_type !== 'clinic'" x-transition class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Number of Licensed Beds</label>
                        <input type="number" x-model="data.licensed_beds" min="1" class="w-40 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Step 4: Complete -->
            <div x-show="currentStep === 3" x-transition>
                <div class="text-center py-8">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">You're All Set!</h2>
                    <p class="text-gray-500 mb-6">Your clinic has been configured. You can always change these settings later.</p>
                    <div class="bg-gray-50 rounded-xl p-4 text-sm text-left max-w-md mx-auto">
                        <p><span class="font-semibold">Clinic:</span> <span x-text="data.name"></span></p>
                        <p><span class="font-semibold">Specialties:</span> <span x-text="data.specialties.join(', ')"></span></p>
                        <p><span class="font-semibold">Type:</span> <span x-text="data.facility_type === 'clinic' ? 'Clinic' : data.facility_type === 'hospital' ? 'Hospital' : 'Multispecialty Hospital'"></span></p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between mt-8 pt-4 border-t">
                <button x-show="currentStep > 0" @click="prevStep()"
                        class="px-6 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Back
                </button>
                <div x-show="currentStep === 0"></div>
                <button @click="nextStep()"
                        class="px-6 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                        :disabled="saving">
                    <span x-show="!saving" x-text="currentStep === 3 ? 'Go to Dashboard' : 'Continue'"></span>
                    <span x-show="saving">Saving...</span>
                </button>
            </div>
        </div>

        <!-- Skip link -->
        <a href="{{ route('dashboard') }}" class="mt-4 text-sm text-gray-400 hover:text-gray-600">Skip setup for now</a>
    </div>

    <script>
    function setupWizard() {
        return {
            currentStep: 0,
            saving: false,
            steps: [
                { label: 'Clinic Info', key: 'clinic-info' },
                { label: 'Specialties', key: 'specialties' },
                { label: 'Facility', key: 'facility' },
                { label: 'Complete', key: 'complete' }
            ],
            data: {
                name: @json($clinic->name ?? ''),
                phone: @json($clinic->phone ?? ''),
                email: @json($clinic->email ?? ''),
                address_line1: @json($clinic->address_line1 ?? ''),
                city: @json($clinic->city ?? ''),
                state: @json($clinic->state ?? ''),
                pincode: @json($clinic->pincode ?? ''),
                gstin: @json($clinic->gstin ?? ''),
                specialties: @json($clinic->specialties ?? []),
                facility_type: @json($clinic->facility_type ?? 'clinic'),
                licensed_beds: @json($clinic->licensed_beds ?? 50),
            },
            async nextStep() {
                this.saving = true;
                try {
                    const stepKey = this.steps[this.currentStep].key;
                    const res = await fetch('{{ route("setup-wizard.save") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ step: stepKey, ...this.data })
                    });
                    const json = await res.json();
                    if (json.redirect) {
                        window.location.href = json.redirect;
                        return;
                    }
                    if (this.currentStep < this.steps.length - 1) {
                        this.currentStep++;
                    }
                } catch (e) { console.error(e); }
                this.saving = false;
            },
            prevStep() {
                if (this.currentStep > 0) this.currentStep--;
            }
        }
    }
    </script>
</body>
</html>
