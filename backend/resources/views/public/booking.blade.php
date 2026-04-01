<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Book Appointment - {{ $clinic->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%); min-height: 100vh; }
        .sora { font-family: 'Sora', sans-serif; }
        .booking-card { background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
        .time-slot { transition: all 0.15s ease; }
        .time-slot:not(.disabled):hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15); }
        .time-slot.selected { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; border-color: transparent; }
        .step-indicator { position: relative; }
        .step-indicator::after { content: ''; position: absolute; top: 50%; left: 100%; width: 60px; height: 2px; background: #e5e7eb; transform: translateY(-50%); }
        .step-indicator:last-child::after { display: none; }
        .step-indicator.active .step-number { background: #3b82f6; color: white; }
        .step-indicator.completed .step-number { background: #22c55e; color: white; }
        .step-indicator.completed::after { background: #22c55e; }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 640px) {
            .step-indicator::after { display: none !important; }
            .step-indicator { gap: 4px; }
        }
    </style>
</head>
<body class="antialiased">
    <div x-data="bookingApp()" class="min-h-screen py-4 sm:py-8 px-3 sm:px-4 pb-8">
        {{-- Header --}}
        <div class="max-w-2xl mx-auto mb-4 text-center">
            <a href="{{ route('public.booking.directory') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">← All clinics</a>
        </div>
        <div class="max-w-2xl mx-auto mb-8 text-center">
            @if($clinic->logo_url)
                <img src="{{ $clinic->logo_url }}" alt="{{ $clinic->name }}" class="w-20 h-20 mx-auto rounded-full mb-4 object-cover shadow-lg">
            @else
                <div class="w-20 h-20 mx-auto rounded-full mb-4 bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                    {{ substr($clinic->name, 0, 1) }}
                </div>
            @endif
            <h1 class="sora text-2xl font-bold text-gray-900">{{ $clinic->name }}</h1>
            <p class="text-gray-500 mt-1">Book Your Appointment Online</p>
            @if($clinic->phone)
                <p class="text-sm text-gray-400 mt-1">📞 {{ $clinic->phone }}</p>
            @endif
        </div>

        {{-- Step Indicators --}}
        <div class="max-w-2xl mx-auto mb-8">
            <div class="flex flex-wrap justify-center items-center gap-2 sm:gap-4 max-w-full">
                <template x-for="(stepName, idx) in ['Select Doctor', 'Choose Time', 'Your Details', 'Confirm']" :key="idx">
                    <div class="step-indicator flex items-center gap-2" :class="{'active': step === idx + 1, 'completed': step > idx + 1}">
                        <div class="step-number w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-sm font-semibold transition-colors" x-text="step > idx + 1 ? '✓' : idx + 1"></div>
                        <span class="text-sm font-medium hidden sm:block" :class="step === idx + 1 ? 'text-blue-600' : 'text-gray-400'" x-text="stepName"></span>
                    </div>
                </template>
            </div>
        </div>

        {{-- Main Card --}}
        <div class="max-w-2xl mx-auto booking-card p-6 sm:p-8">
            {{-- Step 1: Select Doctor & Service --}}
            <div x-show="step === 1" x-transition class="fade-in">
                <h2 class="sora text-xl font-bold text-gray-900 mb-6">Select Doctor & Service</h2>
                
                <div class="space-y-4 mb-6">
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700 mb-2 block">Choose Doctor</span>
                        <select x-model="form.doctor_id" @change="form.service_id = ''" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow">
                            <option value="">Select a doctor</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }} {{ $doctor->specialty ? '— ' . ucfirst($doctor->specialty) : '' }}</option>
                            @endforeach
                        </select>
                    </label>

                    @if($services->count())
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700 mb-2 block">Service Type (Optional)</span>
                        <select x-model="form.service_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow">
                            <option value="">General Consultation</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->duration_mins }} mins) {{ $service->advance_amount ? '— ₹' . $service->advance_amount . ' advance' : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                    @endif
                </div>

                <button @click="nextStep()" :disabled="!form.doctor_id" class="w-full py-3 px-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl disabled:opacity-50 disabled:cursor-not-allowed hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg shadow-blue-200">
                    Continue →
                </button>
            </div>

            {{-- Step 2: Select Date & Time --}}
            <div x-show="step === 2" x-transition class="fade-in">
                <h2 class="sora text-xl font-bold text-gray-900 mb-6">Choose Date & Time</h2>

                {{-- Date Picker --}}
                <div class="mb-6">
                    <span class="text-sm font-medium text-gray-700 mb-3 block">Select Date</span>
                    <div class="flex gap-2 overflow-x-auto pb-2">
                        <template x-for="(date, idx) in availableDates" :key="idx">
                            <button @click="selectDate(date.value)" :class="form.appointment_date === date.value ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:border-blue-300'" class="flex-shrink-0 w-20 py-3 border rounded-xl text-center transition-all">
                                <div class="text-xs font-medium" :class="form.appointment_date === date.value ? 'text-blue-100' : 'text-gray-500'" x-text="date.day"></div>
                                <div class="text-lg font-bold" x-text="date.date"></div>
                                <div class="text-xs" :class="form.appointment_date === date.value ? 'text-blue-100' : 'text-gray-500'" x-text="date.month"></div>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Time Slots --}}
                <div class="mb-6">
                    <span class="text-sm font-medium text-gray-700 mb-3 block">Available Time Slots</span>
                    <div x-show="loadingSlots" class="text-center py-8 text-gray-500">
                        <svg class="animate-spin h-8 w-8 mx-auto text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="mt-2">Loading available slots...</p>
                    </div>
                    <div x-show="!loadingSlots && slots.length" class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                        <template x-for="slot in slots" :key="slot.time">
                            <button @click="slot.available && selectTime(slot.time)" :disabled="!slot.available" :class="{'selected': form.appointment_time === slot.time, 'disabled opacity-40 cursor-not-allowed bg-gray-100': !slot.available}" class="time-slot px-3 py-2 border border-gray-200 rounded-lg text-sm font-medium hover:border-blue-400">
                                <span x-text="slot.display"></span>
                            </button>
                        </template>
                    </div>
                    <div x-show="!loadingSlots && !slots.length" class="text-center py-8 text-gray-500">
                        <p>No slots available for this date. Please select another date.</p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button @click="step = 1" class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                        ← Back
                    </button>
                    <button @click="nextStep()" :disabled="!form.appointment_date || !form.appointment_time" class="flex-1 py-3 px-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl disabled:opacity-50 disabled:cursor-not-allowed hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg shadow-blue-200">
                        Continue →
                    </button>
                </div>
            </div>

            {{-- Step 3: Patient Details --}}
            <div x-show="step === 3" x-transition class="fade-in">
                <h2 class="sora text-xl font-bold text-gray-900 mb-6">Your Details</h2>

                <div class="space-y-4 mb-6">
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-2 block">Full Name *</label>
                        <input type="text" x-model="form.patient_name" placeholder="Enter your full name" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-2 block">Phone Number *</label>
                        <input type="tel" x-model="form.patient_phone" placeholder="10-digit mobile number" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-2 block">Email (Optional)</label>
                        <input type="email" x-model="form.patient_email" placeholder="your@email.com" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-2 block">Date of Birth</label>
                            <input type="date" x-model="form.patient_dob" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-2 block">Gender</label>
                            <select x-model="form.patient_gender" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-2 block">Reason for Visit (Optional)</label>
                        <textarea x-model="form.notes" rows="2" placeholder="Briefly describe your concern..." class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"></textarea>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button @click="step = 2" class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                        ← Back
                    </button>
                    <button @click="nextStep()" :disabled="!form.patient_name || !form.patient_phone || form.patient_phone.length < 10" class="flex-1 py-3 px-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl disabled:opacity-50 disabled:cursor-not-allowed hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg shadow-blue-200">
                        Review Booking →
                    </button>
                </div>
            </div>

            {{-- Step 4: Confirmation --}}
            <div x-show="step === 4" x-transition class="fade-in">
                <h2 class="sora text-xl font-bold text-gray-900 mb-6">Confirm Your Booking</h2>

                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 mb-6">
                    <div class="grid gap-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Doctor</span>
                            <span class="font-semibold text-gray-900" x-text="getSelectedDoctorName()"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Date</span>
                            <span class="font-semibold text-gray-900" x-text="formatDate(form.appointment_date)"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Time</span>
                            <span class="font-semibold text-gray-900" x-text="formatTime(form.appointment_time)"></span>
                        </div>
                        <div class="border-t pt-4 flex justify-between items-center">
                            <span class="text-gray-600">Patient</span>
                            <span class="font-semibold text-gray-900" x-text="form.patient_name"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Phone</span>
                            <span class="font-semibold text-gray-900" x-text="form.patient_phone"></span>
                        </div>
                    </div>
                </div>

                <div x-show="effectiveAdvanceRequired() >= 1" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">💳</span>
                        <div>
                            <p class="font-semibold text-amber-800">Advance Payment Required</p>
                            <p class="text-sm text-amber-700">Pay ₹<span x-text="effectiveAdvanceRequired().toFixed(0)"></span> to confirm your booking</p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button @click="step = 3" class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                        ← Back
                    </button>
                    <template x-if="effectiveAdvanceRequired() >= 1">
                    <button @click="initiatePayment()" :disabled="submitting" class="flex-1 py-3 px-6 bg-gradient-to-r from-green-600 to-green-700 text-white font-semibold rounded-xl disabled:opacity-50 hover:from-green-700 hover:to-green-800 transition-all shadow-lg shadow-green-200 flex items-center justify-center gap-2">
                        <span x-show="!submitting">Pay ₹<span x-text="effectiveAdvanceRequired().toFixed(0)"></span> & Confirm</span>
                        <span x-show="submitting" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                    </template>
                    <template x-if="effectiveAdvanceRequired() < 1">
                    <button @click="confirmBooking()" :disabled="submitting" class="flex-1 py-3 px-6 bg-gradient-to-r from-green-600 to-green-700 text-white font-semibold rounded-xl disabled:opacity-50 hover:from-green-700 hover:to-green-800 transition-all shadow-lg shadow-green-200 flex items-center justify-center gap-2">
                        <span x-show="!submitting">✓ Confirm Booking</span>
                        <span x-show="submitting" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Booking...
                        </span>
                    </button>
                    </template>
                </div>
            </div>

            {{-- Success Screen --}}
            <div x-show="step === 5" x-transition class="fade-in text-center py-8">
                <div class="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="sora text-2xl font-bold text-gray-900 mb-2">Booking Confirmed!</h2>
                <p class="text-gray-600 mb-6">Your appointment has been successfully booked.</p>

                <div class="bg-gray-50 rounded-xl p-6 mb-6 text-left">
                    <div class="grid gap-3">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Appointment ID</span>
                            <span class="font-mono font-semibold text-blue-600" x-text="'#' + bookingResult.id"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Date & Time</span>
                            <span class="font-semibold" x-text="bookingResult.date + ' at ' + bookingResult.time"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Doctor</span>
                            <span class="font-semibold" x-text="bookingResult.doctor"></span>
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-500 mb-4">A confirmation SMS has been sent to your mobile number.</p>

                <div x-show="preVisitUrl" class="mb-6 text-left bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <p class="text-sm font-semibold text-blue-900 mb-2">Pre-visit questionnaire</p>
                    <a :href="preVisitUrl" class="text-blue-600 underline break-all text-sm" target="_blank" rel="noopener">Open pre-visit form</a>
                </div>

                <button @click="resetForm()" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    Book Another Appointment
                </button>
            </div>
        </div>

        {{-- Footer --}}
        <div class="max-w-2xl mx-auto mt-8 text-center text-sm text-gray-400">
            <p>Powered by <span class="font-semibold text-gray-500">ClinicOS</span></p>
        </div>
    </div>

    <script>
        console.log('Public booking page loaded for clinic: {{ $clinic->slug }}');

        function bookingApp() {
            return {
                step: 1,
                loadingSlots: false,
                submitting: false,
                slots: [],
                availableDates: [],
                bookingResult: {},
                preVisitUrl: '',

                form: {
                    doctor_id: '',
                    service_id: '',
                    appointment_date: '',
                    appointment_time: '',
                    patient_name: '',
                    patient_phone: '',
                    patient_email: '',
                    patient_dob: '',
                    patient_gender: '',
                    notes: '',
                    razorpay_payment_id: '',
                    razorpay_order_id: '',
                    razorpay_signature: '',
                    advance_amount_paid: 0,
                },

                doctors: @json($doctors),
                services: @json($services),
                clinicSlug: '{{ $clinic->slug }}',
                clinicName: '{{ $clinic->name }}',
                requireAdvance: {{ $bookingSettings['require_advance'] ? 'true' : 'false' }},
                clinicMinAdvance: {{ (float) ($bookingSettings['min_advance'] ?? 0) }},
                lastOrderAmount: 0,

                init() {
                    console.log('Booking app initialized');
                    this.generateAvailableDates();
                },

                generateAvailableDates() {
                    const dates = [];
                    const today = new Date();
                    const maxDays = {{ $bookingSettings['advance_days'] }};
                    
                    for (let i = 0; i < Math.min(maxDays, 14); i++) {
                        const d = new Date(today);
                        d.setDate(d.getDate() + i);
                        dates.push({
                            value: d.toISOString().split('T')[0],
                            day: d.toLocaleDateString('en-US', { weekday: 'short' }),
                            date: d.getDate(),
                            month: d.toLocaleDateString('en-US', { month: 'short' }),
                        });
                    }
                    this.availableDates = dates;
                    console.log('Available dates generated:', dates.length);
                },

                async nextStep() {
                    if (this.step === 1 && this.form.doctor_id) {
                        this.step = 2;
                        if (!this.form.appointment_date && this.availableDates.length) {
                            await this.selectDate(this.availableDates[0].value);
                        }
                    } else if (this.step === 2 && this.form.appointment_date && this.form.appointment_time) {
                        this.step = 3;
                    } else if (this.step === 3 && this.form.patient_name && this.form.patient_phone) {
                        this.step = 4;
                    }
                },

                async selectDate(date) {
                    this.form.appointment_date = date;
                    this.form.appointment_time = '';
                    await this.loadSlots();
                },

                async loadSlots() {
                    this.loadingSlots = true;
                    console.log('Loading slots for:', this.form.appointment_date);
                    
                    try {
                        const params = new URLSearchParams({
                            date: this.form.appointment_date,
                            doctor_id: this.form.doctor_id,
                            service_id: this.form.service_id || '',
                        });

                        const response = await fetch(`/book/${this.clinicSlug}/slots?${params}`);
                        const data = await response.json();
                        this.slots = data.slots || [];
                        console.log('Slots loaded:', this.slots.length, 'available:', data.available_count);
                    } catch (error) {
                        console.error('Error loading slots:', error);
                        this.slots = [];
                    } finally {
                        this.loadingSlots = false;
                    }
                },

                selectTime(time) {
                    this.form.appointment_time = time;
                },

                getSelectedDoctorName() {
                    const doctor = this.doctors.find(d => d.id == this.form.doctor_id);
                    return doctor ? 'Dr. ' + doctor.name : '';
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                },

                formatTime(timeStr) {
                    if (!timeStr) return '';
                    const [h, m] = timeStr.split(':');
                    const hour = parseInt(h);
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    const hour12 = hour % 12 || 12;
                    return `${hour12}:${m} ${ampm}`;
                },

                effectiveAdvanceRequired() {
                    const svc = this.services.find(d => String(d.id) === String(this.form.service_id));
                    const svcAmt = svc && parseFloat(svc.advance_amount) > 0 ? parseFloat(svc.advance_amount) : 0;
                    const clinicMin = this.requireAdvance ? (parseFloat(this.clinicMinAdvance) || 0) : 0;
                    return Math.max(clinicMin, svcAmt);
                },

                async initiatePayment() {
                    this.submitting = true;
                    console.log('Initiating payment');

                    try {
                        const response = await fetch(`/book/${this.clinicSlug}/create-order`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                amount: this.effectiveAdvanceRequired(),
                                service_id: this.form.service_id,
                                patient_name: this.form.patient_name,
                                patient_phone: this.form.patient_phone,
                            }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.lastOrderAmount = parseFloat(data.amount) || this.effectiveAdvanceRequired();
                            const options = {
                                key: data.key_id,
                                amount: Math.round(this.lastOrderAmount * 100),
                                currency: data.currency,
                                name: this.clinicName,
                                description: 'Appointment Advance Payment',
                                order_id: data.order_id,
                                handler: async (response) => {
                                    console.log('Payment successful:', response.razorpay_payment_id);
                                    this.form.razorpay_payment_id = response.razorpay_payment_id;
                                    this.form.razorpay_order_id = response.razorpay_order_id;
                                    this.form.razorpay_signature = response.razorpay_signature || '';
                                    this.form.advance_amount_paid = this.lastOrderAmount;
                                    await this.confirmBooking();
                                },
                                prefill: {
                                    name: this.form.patient_name,
                                    contact: this.form.patient_phone,
                                    email: this.form.patient_email,
                                },
                                theme: { color: '#3b82f6' },
                                modal: {
                                    ondismiss: () => {
                                        console.log('Razorpay checkout dismissed');
                                        this.submitting = false;
                                    },
                                },
                            };

                            const rzp = new Razorpay(options);
                            rzp.on('payment.failed', (response) => {
                                console.error('Payment failed:', response.error);
                                alert('Payment failed. Please try again.');
                                this.submitting = false;
                            });
                            rzp.open();
                        } else {
                            alert(data.error || 'Could not initiate payment');
                            this.submitting = false;
                        }
                    } catch (error) {
                        console.error('Payment initiation error:', error);
                        alert('Error initiating payment. Please try again.');
                        this.submitting = false;
                    }
                },

                async confirmBooking() {
                    this.submitting = true;
                    console.log('Confirming booking');

                    try {
                        const response = await fetch(`/book/${this.clinicSlug}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(this.form),
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.bookingResult = data.appointment;
                            this.preVisitUrl = data.pre_visit_url || '';
                            this.step = 5;
                            console.log('Booking confirmed:', data.appointment.id, 'pre_visit_url', this.preVisitUrl);
                        } else {
                            alert(data.error || 'Booking failed. Please try again.');
                        }
                    } catch (error) {
                        console.error('Booking error:', error);
                        alert('Error confirming booking. Please try again.');
                    } finally {
                        this.submitting = false;
                    }
                },

                resetForm() {
                    this.step = 1;
                    this.preVisitUrl = '';
                    this.form = {
                        doctor_id: '',
                        service_id: '',
                        appointment_date: '',
                        appointment_time: '',
                        patient_name: '',
                        patient_phone: '',
                        patient_email: '',
                        patient_dob: '',
                        patient_gender: '',
                        notes: '',
                        razorpay_payment_id: '',
                        razorpay_order_id: '',
                        razorpay_signature: '',
                        advance_amount_paid: 0,
                    };
                    this.slots = [];
                    this.bookingResult = {};
                    this.lastOrderAmount = 0;
                },
            };
        }
    </script>
</body>
</html>
