@extends('layouts.app')

@section('title', 'EMR')
@section('breadcrumb', 'Electronic Medical Records')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Electronic Medical Records</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage patient consultations and medical records</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-5 border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['today_visits'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500">Today's Visits</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['in_progress'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500">In Progress</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['completed_today'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500">Completed Today</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_followups'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500">Follow-ups (7 days)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">
        {{-- In Progress Consultations --}}
        <div class="col-span-2 bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900">In-Progress Consultations</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Current active visits requiring documentation</p>
                </div>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($inProgressVisits ?? [] as $visit)
                <a href="{{ route('emr.show', ['patient' => $visit->patient_id, 'visit' => $visit->id]) }}" 
                   class="block px-5 py-4 hover:bg-blue-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                {{ substr($visit->patient->name ?? 'P', 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ $visit->patient->name ?? 'Unknown' }}</p>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-xs text-gray-500">
                                        {{ $visit->patient->age_years ?? 'N/A' }}{{ $visit->patient->sex ? strtoupper(substr($visit->patient->sex, 0, 1)) : '' }}
                                    </span>
                                    <span class="text-xs text-gray-400">•</span>
                                    <span class="text-xs text-gray-500">{{ $visit->chief_complaint ?? 'General Consultation' }}</span>
                                    <span class="text-xs text-gray-400">•</span>
                                    <span class="text-xs text-gray-500">Visit #{{ $visit->visit_number }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                <span class="text-xs text-green-600 font-medium">In Consultation</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Started {{ $visit->started_at?->diffForHumans() ?? 'Just now' }}</p>
                        </div>
                    </div>
                </a>
                @empty
                <div class="px-5 py-8 text-center">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500">No consultations in progress</p>
                    <p class="text-xs text-gray-400 mt-1">Check-in patients from appointments to start consultations</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Waiting Queue / Today's Appointments --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-900">Waiting for Consultation</h3>
                <p class="text-xs text-gray-500 mt-0.5">Checked-in patients without active visits</p>
            </div>
            <div class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                @forelse($todayAppointments ?? [] as $apt)
                <div class="px-5 py-3 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-semibold text-sm">
                                {{ substr($apt->patient->name ?? 'P', 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $apt->patient->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">{{ $apt->scheduled_at?->format('h:i A') }} · {{ ucfirst($apt->appointment_type ?? 'consultation') }}</p>
                            </div>
                        </div>
                        <form action="{{ route('emr.create', $apt->patient) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                Start Visit
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-gray-500 text-sm">
                    No patients waiting
                </div>
                @endforelse
            </div>
            <div class="px-5 py-3 border-t border-gray-100">
                <a href="{{ route('appointments.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                    View All Appointments →
                </a>
            </div>
        </div>
    </div>

    {{-- Recent EMR Activity --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-900">Recent EMR Activity</h3>
            <a href="{{ route('patients.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                View All Patients →
            </a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentVisits ?? [] as $visit)
            <a href="{{ route('emr.show', ['patient' => $visit->patient_id, 'visit' => $visit->id]) }}" 
               class="block px-5 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-600 font-semibold">
                            {{ substr($visit->patient->name ?? 'P', 0, 1) }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ $visit->patient->name ?? 'Unknown' }}</p>
                            <p class="text-sm text-gray-500">{{ $visit->chief_complaint ?? 'General Visit' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">{{ $visit->created_at->format('d M Y') }}</p>
                        <span class="text-xs px-2 py-1 rounded-full 
                            @if($visit->status === 'finalised') bg-green-100 text-green-700
                            @elseif($visit->status === 'draft') bg-amber-100 text-amber-700
                            @else bg-gray-100 text-gray-600 @endif">
                            {{ ucfirst(str_replace('_', ' ', $visit->status)) }}
                        </span>
                    </div>
                </div>
            </a>
            @empty
            <div class="px-5 py-8 text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">No EMR Records Yet</h3>
                <p class="text-gray-500 mb-4">To create EMR records, first select a patient from the Patients list.</p>
                <a href="{{ route('patients.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Go to Patients
                </a>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-bold text-gray-900 mb-4">Quick Actions</h3>
        <div class="flex gap-4">
            <a href="{{ route('patients.index') }}" class="flex items-center gap-3 px-5 py-3 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Select Patient</p>
                    <p class="text-xs text-gray-500">Open patient's EMR</p>
                </div>
            </a>
            <a href="{{ route('appointments.index') }}" class="flex items-center gap-3 px-5 py-3 bg-green-50 hover:bg-green-100 rounded-xl transition-colors">
                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">View Appointments</p>
                    <p class="text-xs text-gray-500">Check-in & start visits</p>
                </div>
            </a>
            <a href="{{ route('prescriptions.index') }}" class="flex items-center gap-3 px-5 py-3 bg-purple-50 hover:bg-purple-100 rounded-xl transition-colors">
                <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Prescriptions</p>
                    <p class="text-xs text-gray-500">View all prescriptions</p>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
