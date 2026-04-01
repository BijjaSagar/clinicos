@extends('layouts.app')

@section('title', 'IPD — ' . ($admission->patient->name ?? 'Patient'))
@section('breadcrumb', 'IPD · ' . ($admission->admission_number ?? 'Admission'))

@section('content')
<div x-data="ipdShow()" class="p-4 sm:p-5 lg:p-7 space-y-5">

    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium" style="background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Back + Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('ipd.index') }}"
           class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div class="flex-1">
            <h1 class="text-xl font-bold text-gray-900 font-display">{{ $admission->patient->name ?? 'Patient' }}</h1>
            <p class="text-sm text-gray-500">Admission #{{ $admission->admission_number }}</p>
        </div>
        @if($admission->status === 'admitted')
        <button @click="showDischargeModal = true"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:shadow-lg"
            style="background:linear-gradient(135deg,#dc2626,#b91c1c);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Discharge Patient
        </button>
        @endif
    </div>

    {{-- Patient Info Card --}}
    <div class="bg-white border border-gray-200 rounded-xl p-5">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-5">
            <div>
                <p class="text-xs font-medium text-gray-400 mb-1">Patient</p>
                <p class="text-sm font-semibold text-gray-900">{{ $admission->patient->name ?? '—' }}</p>
                <p class="text-xs text-gray-500">{{ $admission->patient->phone ?? '' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 mb-1">Age / Gender</p>
                <p class="text-sm font-semibold text-gray-900">
                    {{ $admission->patient->age_years ?? '—' }} yr
                    @if($admission->patient->sex ?? null) · {{ ucfirst($admission->patient->sex) }} @endif
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 mb-1">Ward / Bed</p>
                <p class="text-sm font-semibold text-gray-900">{{ $admission->ward->name ?? '—' }}</p>
                <p class="text-xs text-gray-500">Bed {{ $admission->bed->bed_number ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 mb-1">Admission Date</p>
                <p class="text-sm font-semibold text-gray-900">
                    {{ $admission->admission_date ? \Carbon\Carbon::parse($admission->admission_date)->format('d M Y') : '—' }}
                </p>
                <p class="text-xs text-gray-500">
                    {{ $admission->admission_date ? \Carbon\Carbon::parse($admission->admission_date)->diffForHumans() : '' }}
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 mb-1">Primary Doctor</p>
                <p class="text-sm font-semibold text-gray-900">{{ $admission->primaryDoctor->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-400 mb-1">Diagnosis</p>
                <p class="text-sm font-semibold text-gray-900 truncate" title="{{ $admission->diagnosis_at_admission }}">
                    {{ $admission->diagnosis_at_admission ?? '—' }}
                </p>
                @php
                    $statusMap = [
                        'admitted'    => ['label' => 'Admitted',    'bg' => '#ecfdf5', 'color' => '#059669'],
                        'discharged'  => ['label' => 'Discharged',  'bg' => '#f1f5f9', 'color' => '#64748b'],
                        'transferred' => ['label' => 'Transferred', 'bg' => '#fff7ed', 'color' => '#d97706'],
                        'critical'    => ['label' => 'Critical',    'bg' => '#fff1f2', 'color' => '#dc2626'],
                    ];
                    $s = $statusMap[$admission->status] ?? $statusMap['admitted'];
                @endphp
                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                      style="background:{{ $s['bg'] }};color:{{ $s['color'] }};">
                    {{ $s['label'] }}
                </span>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="border-b border-gray-100 flex overflow-x-auto">
            @foreach(['notes' => 'Progress Notes', 'vitals' => 'Vitals Chart', 'medications' => 'Medications', 'discharge' => 'Discharge Summary'] as $tabKey => $tabLabel)
            <button @click="activeTab = '{{ $tabKey }}'"
                :class="activeTab === '{{ $tabKey }}'
                    ? 'border-b-2 text-blue-600 font-semibold'
                    : 'text-gray-500 hover:text-gray-700'"
                class="px-5 py-4 text-sm whitespace-nowrap transition-colors"
                style="border-color: activeTab === '{{ $tabKey }}' ? '#1447E6' : 'transparent';">
                {{ $tabLabel }}
            </button>
            @endforeach
        </div>

        {{-- Progress Notes Tab --}}
        <div x-show="activeTab === 'notes'" class="p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900">Progress Notes</h3>
                <button @click="showNoteModal = true"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold text-white"
                    style="background:#1447E6;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Note
                </button>
            </div>

            @forelse($progressNotes as $note)
            <div class="border border-gray-100 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">{{ ucfirst($note->note_type ?? 'note') }}</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $note->author->name ?? 'Staff' }}</span>
                    </div>
                    <span class="text-xs text-gray-400">
                        {{ $note->note_date ? \Carbon\Carbon::parse($note->note_date)->format('d M Y') : '' }}
                        {{ $note->note_time ?? '' }}
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    @if($note->subjective)
                    <div><p class="text-xs font-semibold text-gray-400 mb-0.5">Subjective</p><p class="text-gray-700">{{ $note->subjective }}</p></div>
                    @endif
                    @if($note->objective)
                    <div><p class="text-xs font-semibold text-gray-400 mb-0.5">Objective</p><p class="text-gray-700">{{ $note->objective }}</p></div>
                    @endif
                    @if($note->assessment)
                    <div><p class="text-xs font-semibold text-gray-400 mb-0.5">Assessment</p><p class="text-gray-700">{{ $note->assessment }}</p></div>
                    @endif
                    @if($note->plan)
                    <div><p class="text-xs font-semibold text-gray-400 mb-0.5">Plan</p><p class="text-gray-700">{{ $note->plan }}</p></div>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center py-10 text-gray-400 text-sm">No progress notes yet. Add the first note.</div>
            @endforelse
        </div>

        {{-- Vitals Tab --}}
        <div x-show="activeTab === 'vitals'" class="p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900">Vitals Chart</h3>
                <button @click="showVitalsModal = true"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold text-white"
                    style="background:#1447E6;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Record Vitals
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Time</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Temp</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Pulse</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">BP</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">SpO2</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">RR</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Pain</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($vitals as $vital)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-xs text-gray-500">{{ \Carbon\Carbon::parse($vital->recorded_at)->format('d M H:i') }}</td>
                            <td class="px-3 py-2">{{ $vital->temperature ? $vital->temperature.'°C' : '—' }}</td>
                            <td class="px-3 py-2">{{ $vital->pulse ? $vital->pulse.' bpm' : '—' }}</td>
                            <td class="px-3 py-2">
                                @if($vital->bp_systolic && $vital->bp_diastolic)
                                    {{ $vital->bp_systolic }}/{{ $vital->bp_diastolic }}
                                @else —
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if($vital->spo2)
                                    <span class="{{ $vital->spo2 < 95 ? 'text-red-600 font-semibold' : '' }}">{{ $vital->spo2 }}%</span>
                                @else —
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $vital->respiratory_rate ? $vital->respiratory_rate.'/min' : '—' }}</td>
                            <td class="px-3 py-2">{{ $vital->pain_score !== null ? $vital->pain_score.'/10' : '—' }}</td>
                            <td class="px-3 py-2 text-xs text-gray-500">{{ $vital->recordedBy->name ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-3 py-10 text-center text-gray-400 text-sm">No vitals recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Medications Tab --}}
        <div x-show="activeTab === 'medications'" class="p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900">Medication Orders</h3>
                <button class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold text-white" style="background:#1447E6;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Order
                </button>
            </div>
            @forelse($medicationOrders as $med)
            <div class="flex items-center gap-4 p-4 border border-gray-100 rounded-xl">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-900">{{ $med->drug_name ?? '—' }}</p>
                    <p class="text-xs text-gray-500">{{ $med->dose ?? '' }} {{ $med->route ?? '' }} {{ $med->frequency ?? '' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400">Ordered by {{ $med->orderedBy->name ?? '—' }}</p>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">{{ ucfirst($med->status ?? 'active') }}</span>
                </div>
            </div>
            @empty
            <div class="text-center py-10 text-gray-400 text-sm">No medication orders yet.</div>
            @endforelse
        </div>

        {{-- Discharge Summary Tab --}}
        <div x-show="activeTab === 'discharge'" class="p-5" id="discharge">
            @if($admission->status === 'discharged')
            <div class="space-y-4">
                <h3 class="text-sm font-bold text-gray-900">Discharge Summary</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs font-medium text-gray-400 mb-1">Discharge Date</p>
                        <p class="font-semibold text-gray-900">{{ $admission->discharge_date ? \Carbon\Carbon::parse($admission->discharge_date)->format('d M Y, H:i') : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 mb-1">Discharge Type</p>
                        <p class="font-semibold text-gray-900">{{ ucfirst($admission->discharge_type ?? '—') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs font-medium text-gray-400 mb-1">Final Diagnosis</p>
                        <p class="text-gray-700">{{ $admission->final_diagnosis ?? '—' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs font-medium text-gray-400 mb-1">Discharge Notes</p>
                        <p class="text-gray-700">{{ $admission->discharge_notes ?? '—' }}</p>
                    </div>
                </div>
            </div>
            @else
            <form method="POST" action="{{ route('ipd.discharge', $admission) }}" class="space-y-4 max-w-xl">
                @csrf
                <h3 class="text-sm font-bold text-gray-900">Process Discharge</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discharge Type <span class="text-red-500">*</span></label>
                    <select name="discharge_type" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Select type…</option>
                        <option value="recovered">Recovered</option>
                        <option value="lama">LAMA (Left Against Medical Advice)</option>
                        <option value="transfer">Transfer to Another Facility</option>
                        <option value="expired">Expired</option>
                        <option value="improved">Improved / Partial Recovery</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Final Diagnosis <span class="text-red-500">*</span></label>
                    <input type="text" name="final_diagnosis" required
                        value="{{ old('final_diagnosis', $admission->diagnosis_at_admission) }}"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discharge Notes</label>
                    <textarea name="discharge_notes" rows="4"
                        placeholder="Instructions for patient, follow-up recommendations…"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('discharge_notes') }}</textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:shadow-lg"
                        style="background:linear-gradient(135deg,#dc2626,#b91c1c);"
                        onclick="return confirm('Are you sure you want to discharge this patient?')">
                        Confirm Discharge
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>

    {{-- Add Progress Note Modal --}}
    <div x-show="showNoteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showNoteModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900">Add Progress Note</h3>
                    <button @click="showNoteModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('ipd.progress-notes.store', $admission) }}" class="p-6 space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Note Type</label>
                            <select name="note_type" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="doctor">Doctor's Note</option>
                                <option value="nursing">Nursing Note</option>
                                <option value="consultant">Consultant</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" name="note_date" required value="{{ date('Y-m-d') }}"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Time</label>
                        <input type="time" name="note_time" required value="{{ date('H:i') }}"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    @foreach(['subjective' => 'Subjective (S)', 'objective' => 'Objective (O)', 'assessment' => 'Assessment (A)', 'plan' => 'Plan (P)'] as $field => $label)
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ $label }}</label>
                        <textarea name="{{ $field }}" required rows="2"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    </div>
                    @endforeach
                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="showNoteModal = false"
                            class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-semibold text-white rounded-xl" style="background:#1447E6;">Save Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Record Vitals Modal --}}
    <div x-show="showVitalsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showVitalsModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900">Record Vitals</h3>
                    <button @click="showVitalsModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="vitalsForm" class="p-6 space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Temperature (°C)</label>
                            <input type="number" name="temperature" step="0.1" min="30" max="45" placeholder="37.0"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Pulse (bpm)</label>
                            <input type="number" name="pulse" min="20" max="300" placeholder="72"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">BP Systolic</label>
                            <input type="number" name="bp_systolic" min="50" max="250" placeholder="120"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">BP Diastolic</label>
                            <input type="number" name="bp_diastolic" min="30" max="150" placeholder="80"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">SpO2 (%)</label>
                            <input type="number" name="spo2" step="0.1" min="50" max="100" placeholder="98"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Respiratory Rate</label>
                            <input type="number" name="respiratory_rate" min="4" max="60" placeholder="16"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Pain Score (0–10)</label>
                            <input type="number" name="pain_score" min="0" max="10" placeholder="0"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">GCS (3–15)</label>
                            <input type="number" name="gcs" min="3" max="15" placeholder="15"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" rows="2" placeholder="Any relevant observations…"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="showVitalsModal = false"
                            class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
                        <button type="button" @click="saveVitals()"
                            class="px-4 py-2 text-sm font-semibold text-white rounded-xl" style="background:#1447E6;">Save Vitals</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Discharge Modal --}}
    <div x-show="showDischargeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDischargeModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-900">Discharge Patient</h3>
                </div>
                <form method="POST" action="{{ route('ipd.discharge', $admission) }}" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discharge Type <span class="text-red-500">*</span></label>
                        <select name="discharge_type" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select type…</option>
                            <option value="recovered">Recovered</option>
                            <option value="lama">LAMA</option>
                            <option value="transfer">Transfer</option>
                            <option value="expired">Expired</option>
                            <option value="improved">Improved</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Final Diagnosis <span class="text-red-500">*</span></label>
                        <input type="text" name="final_diagnosis" required
                            value="{{ $admission->diagnosis_at_admission }}"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discharge Notes</label>
                        <textarea name="discharge_notes" rows="3"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showDischargeModal = false"
                            class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-semibold text-white rounded-xl"
                            style="background:#dc2626;"
                            onclick="return confirm('Confirm discharge?')">
                            Confirm Discharge
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function ipdShow() {
    return {
        activeTab: 'notes',
        showNoteModal: false,
        showVitalsModal: false,
        showDischargeModal: false,

        async saveVitals() {
            const form = document.getElementById('vitalsForm');
            const data = Object.fromEntries(new FormData(form));
            try {
                const res = await fetch('/ipd/{{ $admission->id }}/vitals', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(data),
                });
                const json = await res.json();
                if (json.success) {
                    this.showVitalsModal = false;
                    location.reload();
                } else {
                    alert('Error saving vitals: ' + (json.message || 'Unknown error'));
                }
            } catch (e) {
                alert('Failed to save vitals');
            }
        }
    };
}
</script>
@endpush
@endsection
