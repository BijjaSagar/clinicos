@extends('layouts.app')

@section('title', 'ABDM')
@section('breadcrumb', 'Ayushman Bharat Digital Mission')

@section('content')
<div class="p-6 space-y-6" x-data="abdmData()">
    {{-- ABDM Status Banner --}}
    <div class="rounded-xl p-6" style="background: linear-gradient(135deg, #0d1117 0%, #0d1f3c 100%);">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center" style="background: rgba(20,71,230,.2); border: 1px solid rgba(20,71,230,.3);">
                <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-white">ABDM Integration</h2>
                <p class="text-gray-400 mt-1">Ayushman Bharat Digital Mission compliance status</p>
            </div>
            <div class="flex gap-2">
                <span class="px-4 py-2 rounded-full text-sm font-semibold" style="background: rgba(5,150,105,.15); color: #6ee7b7;">M1 ✓ Live</span>
                <span class="px-4 py-2 rounded-full text-sm font-semibold" style="background: rgba(5,150,105,.15); color: #6ee7b7;">HFR ✓</span>
                <span class="px-4 py-2 rounded-full text-sm font-semibold" style="background: #1e2535; color: #94a3b8;">M2 In Progress</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- ABHA Creation --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-900">ABHA Creation</h3>
            </div>
            <div class="p-5">
                <div class="text-center py-4">
                    <div class="text-4xl font-extrabold text-blue-600 font-display">{{ $stats['abha_created'] ?? 156 }}</div>
                    <p class="text-gray-500 mt-1">Total ABHA IDs Created</p>
                </div>
                <div class="space-y-3 mt-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">This Month</span>
                        <span class="font-semibold text-gray-900">{{ $stats['abha_this_month'] ?? 23 }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Linked Patients</span>
                        <span class="font-semibold text-green-600">{{ $stats['abha_linked'] ?? 142 }}</span>
                    </div>
                </div>
                <button @click="showAbhaModal = true" class="w-full mt-4 px-4 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    Create New ABHA
                </button>
                <button @click="showLinkModal = true" class="w-full mt-2 px-4 py-2.5 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                    Link Existing ABHA
                </button>
            </div>
        </div>

        {{-- Health Records --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-900">Health Records (FHIR R4)</h3>
            </div>
            <div class="p-5">
                <div class="text-center py-4">
                    <div class="text-4xl font-extrabold text-green-600 font-display">{{ $stats['records_shared'] ?? 892 }}</div>
                    <p class="text-gray-500 mt-1">Records Shared</p>
                </div>
                <div class="space-y-3 mt-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Prescriptions</span>
                        <span class="font-semibold text-gray-900">{{ $stats['prescriptions'] ?? 456 }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Diagnostic Reports</span>
                        <span class="font-semibold text-gray-900">{{ $stats['diagnostics'] ?? 234 }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Discharge Summaries</span>
                        <span class="font-semibold text-gray-900">{{ $stats['discharge'] ?? 89 }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Consent Management --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-900">Consent Management</h3>
            </div>
            <div class="p-5">
                <div class="text-center py-4">
                    <div class="text-4xl font-extrabold text-purple-600 font-display">{{ $stats['consents'] ?? 67 }}</div>
                    <p class="text-gray-500 mt-1">Active Consents</p>
                </div>
                <div class="space-y-3 mt-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Pending Requests</span>
                        <span class="font-semibold text-amber-600">{{ $stats['pending_consents'] ?? 5 }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Expired</span>
                        <span class="font-semibold text-gray-500">{{ $stats['expired_consents'] ?? 12 }}</span>
                    </div>
                </div>
                <button class="w-full mt-4 px-4 py-2.5 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                    View All Consents
                </button>
            </div>
        </div>
    </div>

    {{-- HFR Details --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-900">Health Facility Registry (HFR)</h3>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Facility ID</p>
                    <p class="font-semibold text-gray-900">{{ auth()->user()->clinic->hfr_facility_id ?? 'IN0410000123' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">HFR ID</p>
                    <p class="font-semibold text-gray-900">{{ auth()->user()->clinic->hfr_id ?? 'HFR-MH-12345' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Last Sync</p>
                    <p class="font-semibold text-gray-900">{{ now()->subHours(2)->format('d M Y, h:i A') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ABHA Creation Modal --}}
    <div x-show="showAbhaModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click.self="showAbhaModal = false; abhaStep = 1">
        
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg" @click.stop>
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Create ABHA ID</h3>
                    <p class="text-sm text-gray-500">Step <span x-text="abhaStep"></span> of 3</p>
                </div>
                <button @click="showAbhaModal = false; abhaStep = 1" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Step 1: Choose Verification Method --}}
            <div x-show="abhaStep === 1" class="p-6 space-y-4">
                <p class="text-gray-600">Select verification method for ABHA creation:</p>
                
                <div class="space-y-3">
                    <label class="flex items-center gap-4 p-4 border rounded-xl cursor-pointer hover:border-blue-300 hover:bg-blue-50/50 transition-colors" :class="verificationMethod === 'aadhaar' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                        <input type="radio" name="verification_method" value="aadhaar" x-model="verificationMethod" class="w-4 h-4 text-blue-600">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">Aadhaar OTP</p>
                            <p class="text-sm text-gray-500">Verify using Aadhaar linked mobile number</p>
                        </div>
                        <svg class="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </label>
                    
                    <label class="flex items-center gap-4 p-4 border rounded-xl cursor-pointer hover:border-blue-300 hover:bg-blue-50/50 transition-colors" :class="verificationMethod === 'mobile' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                        <input type="radio" name="verification_method" value="mobile" x-model="verificationMethod" class="w-4 h-4 text-blue-600">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">Mobile OTP</p>
                            <p class="text-sm text-gray-500">Verify using mobile number (limited features)</p>
                        </div>
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </label>
                </div>

                <div class="pt-4 flex justify-end">
                    <button @click="abhaStep = 2" class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                        Continue
                    </button>
                </div>
            </div>

            {{-- Step 2: Enter Details --}}
            <div x-show="abhaStep === 2" class="p-6 space-y-4">
                <div x-show="verificationMethod === 'aadhaar'">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Aadhaar Number</label>
                    <input type="text" placeholder="XXXX XXXX XXXX" maxlength="14" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg tracking-widest">
                    <p class="text-xs text-gray-500 mt-1">Enter 12-digit Aadhaar number</p>
                </div>
                
                <div x-show="verificationMethod === 'mobile'">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Mobile Number</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-4 py-3 rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 text-gray-500">+91</span>
                        <input type="tel" placeholder="9876543210" maxlength="10" class="flex-1 px-4 py-3 rounded-r-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg tracking-wider">
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-amber-800">Patient Consent Required</p>
                            <p class="text-xs text-amber-700 mt-1">Ensure patient has given verbal consent for ABHA creation before proceeding.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-between">
                    <button @click="abhaStep = 1" class="px-6 py-2.5 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                        Back
                    </button>
                    <button @click="abhaStep = 3" class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                        Send OTP
                    </button>
                </div>
            </div>

            {{-- Step 3: Verify OTP --}}
            <div x-show="abhaStep === 3" class="p-6 space-y-4">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <p class="text-gray-600">Enter the 6-digit OTP sent to the registered mobile number</p>
                </div>

                <div class="flex justify-center gap-2">
                    <input type="text" x-model="otp1" maxlength="1" @input="$el.value.length === 1 && $refs.otp2.focus()" class="w-12 h-14 text-center text-xl font-bold rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <input type="text" x-model="otp2" x-ref="otp2" maxlength="1" @input="$el.value.length === 1 && $refs.otp3.focus()" class="w-12 h-14 text-center text-xl font-bold rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <input type="text" x-model="otp3" x-ref="otp3" maxlength="1" @input="$el.value.length === 1 && $refs.otp4.focus()" class="w-12 h-14 text-center text-xl font-bold rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <input type="text" x-model="otp4" x-ref="otp4" maxlength="1" @input="$el.value.length === 1 && $refs.otp5.focus()" class="w-12 h-14 text-center text-xl font-bold rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <input type="text" x-model="otp5" x-ref="otp5" maxlength="1" @input="$el.value.length === 1 && $refs.otp6.focus()" class="w-12 h-14 text-center text-xl font-bold rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <input type="text" x-model="otp6" x-ref="otp6" maxlength="1" class="w-12 h-14 text-center text-xl font-bold rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="text-center">
                    <button @click="resendOtp()" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Resend OTP</button>
                    <span class="text-gray-400 mx-2">•</span>
                    <span class="text-sm text-gray-500">Expires in <span x-text="otpTimer">2:30</span></span>
                </div>

                {{-- Error/Success Messages --}}
                <div x-show="errorMsg" class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700" x-text="errorMsg"></div>
                <div x-show="successMsg" class="bg-green-50 border border-green-200 rounded-xl p-3 text-sm text-green-700" x-text="successMsg"></div>

                <div class="pt-4 flex justify-between">
                    <button @click="abhaStep = 2" class="px-6 py-2.5 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                        Back
                    </button>
                    <button @click="verifyOtp()" :disabled="loading" class="px-6 py-2.5 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors disabled:opacity-50">
                        <span x-show="!loading">Verify & Create ABHA</span>
                        <span x-show="loading">Verifying...</span>
                    </button>
                </div>
            </div>

            {{-- Step 4: Success --}}
            <div x-show="abhaStep === 4" class="p-6 space-y-4">
                <div class="text-center py-6">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">ABHA Created Successfully!</h3>
                    <div class="mt-4 p-4 bg-gray-50 rounded-xl">
                        <p class="text-sm text-gray-500">ABHA Number</p>
                        <p class="text-2xl font-bold text-blue-600 font-mono tracking-wider" x-text="createdAbhaId"></p>
                        <p class="text-sm text-gray-500 mt-2">ABHA Address</p>
                        <p class="text-lg font-semibold text-gray-900" x-text="createdAbhaAddress"></p>
                    </div>
                </div>

                <div class="pt-4">
                    <button @click="showAbhaModal = false; abhaStep = 1; window.location.reload()" class="w-full px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Link Existing ABHA Modal --}}
    <div x-show="showLinkModal" 
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click.self="showLinkModal = false">
        
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.stop>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Link Existing ABHA</h3>
                <button @click="showLinkModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ABHA Number (14 digits)</label>
                    <input type="text" x-model="linkAbhaId" placeholder="12-3456-7890-1234" maxlength="14" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg tracking-wider">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ABHA Address (optional)</label>
                    <input type="text" x-model="linkAbhaAddress" placeholder="username@abdm" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Link to Patient</label>
                    <select x-model="linkPatientId" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select patient...</option>
                        @foreach(\App\Models\Patient::where('clinic_id', auth()->user()->clinic_id)->whereNull('abha_id')->orderBy('name')->get() as $patient)
                        <option value="{{ $patient->id }}">{{ $patient->name }} ({{ $patient->phone }})</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="linkError" class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700" x-text="linkError"></div>

                <button @click="linkAbha()" :disabled="!linkAbhaId || !linkPatientId || loading" class="w-full px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors disabled:opacity-50">
                    Link ABHA to Patient
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
console.log('ABDM Centre loaded');

document.addEventListener('alpine:init', () => {
    Alpine.data('abdmData', () => ({
        showAbhaModal: false,
        showLinkModal: false,
        abhaStep: 1,
        verificationMethod: 'aadhaar',
        loading: false,
        
        // Form data
        aadhaarNumber: '',
        mobileNumber: '',
        txnId: '',
        otp1: '', otp2: '', otp3: '', otp4: '', otp5: '', otp6: '',
        otpTimer: '2:30',
        
        // Results
        createdAbhaId: '',
        createdAbhaAddress: '',
        errorMsg: '',
        successMsg: '',
        
        // Link modal
        linkAbhaId: '',
        linkAbhaAddress: '',
        linkPatientId: '',
        linkError: '',
        
        async generateOtp() {
            this.loading = true;
            this.errorMsg = '';
            
            try {
                const endpoint = this.verificationMethod === 'aadhaar' 
                    ? '{{ route('abdm.aadhaar.otp') }}'
                    : '{{ route('abdm.mobile.otp') }}';
                
                const payload = this.verificationMethod === 'aadhaar'
                    ? { aadhaar: this.aadhaarNumber.replace(/\s/g, '') }
                    : { mobile: this.mobileNumber };
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify(payload),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.txnId = data.txnId;
                    this.abhaStep = 3;
                    this.startOtpTimer();
                } else {
                    this.errorMsg = data.error || 'Failed to generate OTP';
                }
            } catch (e) {
                console.error('OTP generation error:', e);
                this.errorMsg = 'Network error. Please try again.';
            } finally {
                this.loading = false;
            }
        },
        
        async verifyOtp() {
            this.loading = true;
            this.errorMsg = '';
            
            const otp = this.otp1 + this.otp2 + this.otp3 + this.otp4 + this.otp5 + this.otp6;
            
            if (otp.length !== 6) {
                this.errorMsg = 'Please enter complete 6-digit OTP';
                this.loading = false;
                return;
            }
            
            try {
                const endpoint = this.verificationMethod === 'aadhaar'
                    ? '{{ route('abdm.aadhaar.verify') }}'
                    : '{{ route('abdm.mobile.verify') }}';
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        txnId: this.txnId,
                        otp: otp,
                    }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.createdAbhaId = data.abha_id;
                    this.createdAbhaAddress = data.abha_address;
                    this.abhaStep = 4;
                } else {
                    this.errorMsg = data.error || 'OTP verification failed';
                }
            } catch (e) {
                console.error('OTP verification error:', e);
                this.errorMsg = 'Network error. Please try again.';
            } finally {
                this.loading = false;
            }
        },
        
        async linkAbha() {
            this.loading = true;
            this.linkError = '';
            
            try {
                const response = await fetch('{{ route('abdm.link') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        patient_id: this.linkPatientId,
                        abha_id: this.linkAbhaId.replace(/[^0-9]/g, ''),
                        abha_address: this.linkAbhaAddress,
                    }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('ABHA linked successfully!');
                    this.showLinkModal = false;
                    window.location.reload();
                } else {
                    this.linkError = data.error || 'Failed to link ABHA';
                }
            } catch (e) {
                console.error('Link ABHA error:', e);
                this.linkError = 'Network error. Please try again.';
            } finally {
                this.loading = false;
            }
        },
        
        startOtpTimer() {
            let seconds = 150; // 2:30
            const timer = setInterval(() => {
                seconds--;
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                this.otpTimer = `${mins}:${secs.toString().padStart(2, '0')}`;
                
                if (seconds <= 0) {
                    clearInterval(timer);
                    this.otpTimer = '0:00';
                }
            }, 1000);
        },
        
        resendOtp() {
            this.otp1 = this.otp2 = this.otp3 = this.otp4 = this.otp5 = this.otp6 = '';
            this.generateOtp();
        }
    }));
});
</script>
@endpush
@endsection
