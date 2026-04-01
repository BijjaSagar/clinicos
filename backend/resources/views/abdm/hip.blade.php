@extends('layouts.app')

@section('title', 'ABDM Health Information Provider')

@section('breadcrumb', 'ABDM / HIP')

@section('content')
<div x-data="hipDashboard()" class="p-6 space-y-6">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-2xl p-6 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold">Health Information Provider (HIP)</h1>
                <p class="text-white/80 mt-1">ABDM M2 - Share health records with patients via ABHA</p>
            </div>
            <div class="ml-auto">
                @if($stats['hip_registered'])
                <div class="flex items-center gap-2 px-4 py-2 bg-green-500 rounded-lg">
                    <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                    <span class="font-semibold">HIP Active</span>
                </div>
                @else
                <button @click="registerHIP()" class="px-4 py-2 bg-white text-orange-600 font-semibold rounded-lg hover:bg-orange-50 transition-colors">
                    Register as HIP
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-2xl">🔗</div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['linked_patients'] }}</div>
                    <div class="text-sm text-gray-500">ABHA Linked Patients</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center text-2xl">📤</div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['records_shared'] }}</div>
                    <div class="text-sm text-gray-500">Records Shared</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-2xl">⏳</div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['pending_consents'] }}</div>
                    <div class="text-sm text-gray-500">Pending Consents</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center text-2xl">🏥</div>
                <div>
                    <div class="text-sm font-medium text-gray-500">HFR ID</div>
                    <div class="font-mono text-sm text-gray-900">{{ $clinic?->hfr_id ?? 'Not Registered' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- HIP Workflow --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50">
                <h2 class="font-semibold text-gray-900">HIP Workflow</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    {{-- Step 1 --}}
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $clinic?->hfr_id ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                            {{ $clinic?->hfr_id ? '✓' : '1' }}
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900">Register with HFR</h4>
                            <p class="text-sm text-gray-500 mt-1">Register your facility on Health Facility Registry</p>
                            @if(!$clinic?->hfr_id)
                            <a href="{{ route('abdm.index') }}" class="inline-flex items-center mt-2 text-sm text-orange-600 hover:text-orange-700">
                                Go to ABDM Centre →
                            </a>
                            @endif
                        </div>
                    </div>

                    {{-- Step 2 --}}
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $stats['hip_registered'] ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                            {{ $stats['hip_registered'] ? '✓' : '2' }}
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900">Register as HIP</h4>
                            <p class="text-sm text-gray-500 mt-1">Enable Health Information Provider capabilities</p>
                            @if($clinic?->hfr_id && !$stats['hip_registered'])
                            <button @click="registerHIP()" class="inline-flex items-center mt-2 px-3 py-1.5 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700">
                                Register Now
                            </button>
                            @endif
                        </div>
                    </div>

                    {{-- Step 3 --}}
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $stats['linked_patients'] > 0 ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                            {{ $stats['linked_patients'] > 0 ? '✓' : '3' }}
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900">Link Patient Records</h4>
                            <p class="text-sm text-gray-500 mt-1">Link care contexts to patient's ABHA</p>
                        </div>
                    </div>

                    {{-- Step 4 --}}
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold bg-gray-200 text-gray-600">4</div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900">Respond to Consent Requests</h4>
                            <p class="text-sm text-gray-500 mt-1">Approve or deny patient consent requests</p>
                        </div>
                    </div>

                    {{-- Step 5 --}}
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold bg-gray-200 text-gray-600">5</div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900">Share Health Records</h4>
                            <p class="text-sm text-gray-500 mt-1">FHIR bundles shared securely via ABDM network</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Link Care Context --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50">
                <h2 class="font-semibold text-gray-900">Link Care Context</h2>
            </div>
            <div class="p-6">
                <form @submit.prevent="linkCareContext()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Patient</label>
                            <select x-model="linkForm.patient_id" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                <option value="">Choose patient with ABHA...</option>
                                @php
                                    $abhaPatients = \App\Models\Patient::where('clinic_id', auth()->user()->clinic_id)
                                        ->whereNotNull('abha_id')
                                        ->get(['id', 'name', 'abha_id']);
                                @endphp
                                @foreach($abhaPatients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->name }} ({{ $patient->abha_id }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Care Context Type</label>
                            <select x-model="linkForm.care_context_type" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                <option value="OPConsultation">OP Consultation</option>
                                <option value="Prescription">Prescription</option>
                                <option value="DiagnosticReport">Diagnostic Report</option>
                                <option value="DischargeSummary">Discharge Summary</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Visit (Optional)</label>
                            <select x-model="linkForm.visit_id" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                <option value="">Latest visit</option>
                                <!-- Visits would be loaded dynamically based on selected patient -->
                            </select>
                        </div>

                        <button type="submit" :disabled="!linkForm.patient_id || linking" class="w-full py-3 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                            <svg x-show="linking" class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="linking ? 'Linking...' : '🔗 Link Care Context'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Health Information Types --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50">
            <h2 class="font-semibold text-gray-900">Supported Health Information Types</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="text-2xl mb-2">🩺</div>
                    <h4 class="font-medium text-gray-900">OP Consultation</h4>
                    <p class="text-xs text-gray-500 mt-1">Clinical notes, diagnosis, treatment plans</p>
                </div>
                <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                    <div class="text-2xl mb-2">💊</div>
                    <h4 class="font-medium text-gray-900">Prescription</h4>
                    <p class="text-xs text-gray-500 mt-1">Medications, dosage, instructions</p>
                </div>
                <div class="p-4 bg-purple-50 rounded-lg border border-purple-100">
                    <div class="text-2xl mb-2">🔬</div>
                    <h4 class="font-medium text-gray-900">Diagnostic Report</h4>
                    <p class="text-xs text-gray-500 mt-1">Lab reports, imaging results</p>
                </div>
                <div class="p-4 bg-amber-50 rounded-lg border border-amber-100">
                    <div class="text-2xl mb-2">📋</div>
                    <h4 class="font-medium text-gray-900">Discharge Summary</h4>
                    <p class="text-xs text-gray-500 mt-1">Hospital stay summary, follow-up</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
console.log('HIP Dashboard loaded');

function hipDashboard() {
    return {
        linking: false,
        linkForm: {
            patient_id: '',
            visit_id: '',
            care_context_type: 'OPConsultation',
        },

        async registerHIP() {
            if (!confirm('Register this facility as a Health Information Provider?')) return;

            try {
                const response = await fetch('/abdm/hip/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    alert('Successfully registered as HIP!');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('HIP registration error:', error);
                alert('Registration failed. Please try again.');
            }
        },

        async linkCareContext() {
            if (!this.linkForm.patient_id) return;

            this.linking = true;

            try {
                const response = await fetch('/abdm/hip/link/' + this.linkForm.patient_id, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.linkForm),
                });

                const data = await response.json();

                if (data.success) {
                    alert('Care context linked successfully!\nReference: ' + data.care_context_reference);
                    this.linkForm = { patient_id: '', visit_id: '', care_context_type: 'OPConsultation' };
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Link error:', error);
                alert('Linking failed. Please try again.');
            } finally {
                this.linking = false;
            }
        },
    };
}
</script>
@endsection
