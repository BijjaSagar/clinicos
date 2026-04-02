@extends('layouts.app')

@section('title', 'OPD Queue Management')

@section('content')
<div
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6"
    x-data="{
        walkinOpen: false,
        currentTime: '',
        refreshCountdown: 30,
        doctorFilter: '{{ request('doctor_id', '') }}',
        init() {
            this.tick();
            setInterval(() => this.tick(), 1000);
            setInterval(() => {
                this.refreshCountdown--;
                if (this.refreshCountdown <= 0) {
                    window.location.reload();
                }
            }, 1000);
        },
        tick() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        },
        async changeStatus(appointmentId, status, selectEl) {
            try {
                const url = '/opd/' + appointmentId + '/status';
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status })
                });
                if (res.ok) {
                    window.location.reload();
                } else {
                    alert('Failed to update status');
                }
            } catch (e) {
                alert('Network error');
            }
        }
    }"
    x-init="init()"
>

    {{-- Top Bar --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">OPD Queue Management</h1>
                <p class="text-sm text-gray-500">Outpatient department queue &amp; appointments</p>
            </div>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            {{-- Auto-refresh indicator --}}
            <div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 rounded-lg text-xs text-gray-500 font-medium">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                Auto-refresh in <span class="font-mono font-bold text-gray-700" x-text="refreshCountdown + 's'"></span>
            </div>
            {{-- Real-time clock --}}
            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-indigo-50 border border-indigo-200 rounded-lg">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-mono font-semibold text-indigo-700" x-text="currentTime"></span>
            </div>
            {{-- Add Walk-in Button --}}
            <button @click="walkinOpen = true"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Add Walk-in
            </button>
        </div>
    </div>

    {{-- Filter Bar: Date + Doctor --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('opd.queue') }}" class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <div class="flex items-center gap-2">
                <label class="text-sm font-semibold text-gray-700">Date:</label>
                <input type="date" name="date" value="{{ $date }}"
                    class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-colors">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-semibold text-gray-700">Doctor:</label>
                <select name="doctor_id"
                    class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-colors bg-white min-w-[180px]">
                    <option value="">All Doctors</option>
                    @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>{{ $doctor->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-700 transition-colors">
                Filter
            </button>
            @if($date !== today()->toDateString() || request('doctor_id'))
                <a href="{{ route('opd.queue') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 mb-6">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        {{-- Total --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-blue-500"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Total</p>
                    <p class="text-2xl font-extrabold text-gray-900 leading-none">{{ $stats['total'] }}</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        {{-- Waiting --}}
        <div class="bg-amber-50 rounded-2xl border border-amber-200 shadow-sm p-4 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-amber-500"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-amber-600 uppercase tracking-wide mb-1">Waiting</p>
                    <p class="text-2xl font-extrabold text-amber-700 leading-none">{{ $stats['waiting'] }}</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        {{-- In Progress --}}
        <div class="bg-indigo-50 rounded-2xl border border-indigo-200 shadow-sm p-4 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-indigo-500"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wide mb-1">In Progress</p>
                    <p class="text-2xl font-extrabold text-indigo-700 leading-none">{{ $stats['in_progress'] }}</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>
        {{-- Completed --}}
        <div class="bg-emerald-50 rounded-2xl border border-emerald-200 shadow-sm p-4 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-emerald-500"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide mb-1">Completed</p>
                    <p class="text-2xl font-extrabold text-emerald-700 leading-none">{{ $stats['completed'] }}</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        {{-- No-Show / Cancelled --}}
        <div class="bg-red-50 rounded-2xl border border-red-200 shadow-sm p-4 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-red-500"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-red-600 uppercase tracking-wide mb-1">No-Show / Cancelled</p>
                    <p class="text-2xl font-extrabold text-red-700 leading-none">{{ $stats['cancelled'] }}</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Queue Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-bold text-gray-900">Queue</h3>
                <span class="px-2.5 py-0.5 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-full">{{ $stats['total'] }} patients</span>
            </div>
            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
                <thead>
                    <tr class="bg-gray-50/70 border-b border-gray-100">
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-20">Token #</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Doctor</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Time Slot</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Chief Complaint</th>
                        <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Wait Time</th>
                        <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($appointments as $index => $appt)
                    @php
                        $statusColors = [
                            'waiting'     => 'bg-amber-100 text-amber-800 ring-1 ring-amber-300',
                            'confirmed'   => 'bg-amber-100 text-amber-800 ring-1 ring-amber-300',
                            'in_progress' => 'bg-blue-100 text-blue-800 ring-1 ring-blue-300',
                            'completed'   => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300',
                            'done'        => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300',
                            'cancelled'   => 'bg-red-100 text-red-700 ring-1 ring-red-300',
                            'no_show'     => 'bg-gray-100 text-gray-600 ring-1 ring-gray-300',
                        ];
                        $colorClass = $statusColors[$appt->queue_status] ?? $statusColors[$appt->status] ?? 'bg-gray-100 text-gray-500 ring-1 ring-gray-300';
                        $isEmergency = ($appt->priority ?? '') === 'emergency' || ($appt->type ?? '') === 'emergency';
                        $waitTime = null;
                        if ($appt->appointment_time && in_array($appt->status, ['confirmed', 'in_progress'])) {
                            $apptTime = \Carbon\Carbon::parse($date . ' ' . $appt->appointment_time);
                            if (now()->gt($apptTime)) {
                                $waitTime = $apptTime->diffForHumans(now(), ['parts' => 1, 'short' => true]);
                                $waitMinutes = $apptTime->diffInMinutes(now());
                            }
                        }
                    @endphp
                    <tr class="hover:bg-blue-50/30 transition-colors group {{ $isEmergency ? 'border-l-4 border-l-red-500 bg-red-50/30' : '' }}">
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center justify-center w-10 h-10 bg-indigo-100 text-indigo-700 font-extrabold text-base rounded-xl shadow-sm">
                                {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                @php
                                    $avatarColors = ['#2563EB','#7C3AED','#059669','#DC2626','#D97706','#0891B2'];
                                    $initials = strtoupper(substr($appt->patient->name ?? 'P', 0, 1));
                                    $avatarBg = $avatarColors[ord($initials) % count($avatarColors)];
                                @endphp
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm"
                                     style="background: {{ $avatarBg }};">
                                    {{ $initials }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-700 transition-colors">{{ $appt->patient->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $appt->patient->phone ?? '' }}
                                        @if($appt->patient->age ?? null)
                                            &middot; {{ $appt->patient->age }} yrs
                                        @endif
                                        @if($appt->patient->gender ?? null)
                                            / {{ ucfirst(substr($appt->patient->gender, 0, 1)) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-sm text-gray-800 font-medium">{{ $appt->doctor->name ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <p class="text-sm text-gray-800 font-medium">
                                {{ $appt->appointment_time ? \Carbon\Carbon::parse($appt->appointment_time)->format('h:i A') : '—' }}
                            </p>
                        </td>
                        <td class="px-4 py-4 max-w-[200px]">
                            <p class="text-sm text-gray-600 truncate" title="{{ $appt->chief_complaint }}">{{ $appt->chief_complaint ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($waitTime)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ ($waitMinutes ?? 0) > 30 ? 'bg-red-100 text-red-700' : (($waitMinutes ?? 0) > 15 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/>
                                    </svg>
                                    {{ $waitMinutes }}m
                                </span>
                            @elseif(in_array($appt->status, ['completed', 'cancelled', 'no_show']))
                                <span class="text-xs text-gray-400">—</span>
                            @else
                                <span class="text-xs text-gray-400">Not yet</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            <select
                                onchange="
                                    const apptId = '{{ $appt->id }}';
                                    const status = this.value;
                                    fetch('/opd/' + apptId + '/status', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({ status })
                                    }).then(r => { if (r.ok) window.location.reload(); else alert('Update failed'); }).catch(() => alert('Network error'));
                                "
                                class="text-xs font-semibold px-3 py-1.5 rounded-full border-0 cursor-pointer focus:ring-2 focus:ring-indigo-500/30 appearance-none {{ $colorClass }}"
                            >
                                <option value="confirmed"   {{ $appt->status === 'confirmed'   ? 'selected' : '' }}>Waiting</option>
                                <option value="in_progress" {{ $appt->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed"   {{ $appt->status === 'completed'   ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled"   {{ $appt->status === 'cancelled'   ? 'selected' : '' }}>Cancelled</option>
                                <option value="no_show"     {{ $appt->status === 'no_show'     ? 'selected' : '' }}>No Show</option>
                            </select>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                @if(($appt->patient_id ?? null) && in_array($appt->status, ['confirmed', 'in_progress']))
                                    <a href="{{ route('emr.create', $appt->patient_id) }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm whitespace-nowrap">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        Start Consultation
                                    </a>
                                @endif
                                @if($appt->patient_id ?? null)
                                    <a href="{{ route('patients.show', $appt->patient_id) }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 hover:text-gray-800 transition-colors whitespace-nowrap">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-20 text-center">
                            <div class="flex flex-col items-center gap-4 max-w-sm mx-auto">
                                <div class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-base font-bold text-gray-700">No appointments for this date</p>
                                    <p class="text-sm text-gray-400 mt-1">Add a walk-in patient or select a different date</p>
                                </div>
                                <button @click="walkinOpen = true"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Add Walk-in Patient
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Walk-in Modal --}}
    <div x-show="walkinOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="walkinOpen = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="walkinOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg" @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between p-6 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">Add Walk-in Patient</h2>
                </div>
                <button @click="walkinOpen = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('opd.walkin') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Patient <span class="text-red-500">*</span></label>
                    <select name="patient_id" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-colors bg-white">
                        <option value="">Search or select patient...</option>
                        @foreach($patients ?? [] as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->name }} ({{ $patient->phone ?? 'No phone' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Doctor <span class="text-red-500">*</span></label>
                    <select name="doctor_id" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-colors bg-white">
                        <option value="">Select doctor...</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Chief Complaint</label>
                    <input type="text" name="chief_complaint" placeholder="e.g. Fever, headache, cough"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-colors">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                        <input type="date" name="appointment_date" value="{{ $date }}" required
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Time <span class="text-red-500">*</span></label>
                        <input type="time" name="appointment_time" required
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-colors">
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="walkinOpen = false" class="px-5 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition-colors shadow-sm">
                        Add to Queue
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
