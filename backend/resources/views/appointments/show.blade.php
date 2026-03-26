@extends('layouts.app')

@section('title', 'Appointment Details')
@section('breadcrumb', 'Appointment')

@section('content')
<div class="p-6">
    <div class="max-w-3xl mx-auto space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Appointment Details</h1>
                <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($appointment->scheduled_at)->format('l, d M Y') }}</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-semibold {{ 
                $appointment->status === 'completed' ? 'bg-green-100 text-green-700' : 
                ($appointment->status === 'confirmed' ? 'bg-blue-100 text-blue-700' : 
                ($appointment->status === 'checked_in' ? 'bg-amber-100 text-amber-700' : 
                ($appointment->status === 'in_consultation' ? 'bg-purple-100 text-purple-700' : 
                ($appointment->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700')))) 
            }}">
                {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
            </span>
        </div>

        {{-- Appointment Info Card --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-900">Appointment Information</h3>
            </div>
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Time</p>
                        <p class="font-semibold text-gray-900 text-lg">{{ \Carbon\Carbon::parse($appointment->scheduled_at)->format('h:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Duration</p>
                        <p class="font-semibold text-gray-900">{{ $appointment->duration_mins ?? 30 }} minutes</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Type</p>
                        <p class="font-semibold text-gray-900">{{ ucfirst($appointment->appointment_type ?? 'Consultation') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Booking Source</p>
                        <p class="font-semibold text-gray-900">{{ ucfirst($appointment->booking_source ?? 'Web') }}</p>
                    </div>
                </div>
                @if($appointment->notes)
                <div>
                    <p class="text-sm text-gray-500">Notes</p>
                    <p class="text-gray-900">{{ $appointment->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Patient Info Card --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-bold text-gray-900">Patient</h3>
                @if($appointment->patient)
                <a href="{{ route('patients.show', $appointment->patient) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">View Profile →</a>
                @endif
            </div>
            <div class="p-5">
                @if($appointment->patient)
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <span class="text-blue-600 font-bold text-lg">{{ strtoupper(substr($appointment->patient->name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $appointment->patient->name }}</p>
                        <p class="text-sm text-gray-500">{{ $appointment->patient->phone }}</p>
                    </div>
                </div>
                @else
                <p class="text-gray-400">No patient linked</p>
                @endif
            </div>
        </div>

        {{-- Doctor Info Card --}}
        @if($appointment->doctor)
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-900">Doctor</h3>
            </div>
            <div class="p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <span class="text-green-600 font-bold text-lg">{{ strtoupper(substr($appointment->doctor->name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $appointment->doctor->name }}</p>
                        <p class="text-sm text-gray-500">{{ $appointment->doctor->specialty ?? 'General' }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Actions --}}
        @if($appointment->status !== 'completed' && $appointment->status !== 'cancelled')
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-900">Actions</h3>
            </div>
            <div class="p-5 flex flex-wrap gap-3">
                @if($appointment->status === 'confirmed')
                <form action="{{ route('appointments.status', $appointment) }}" method="POST" class="inline">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="checked_in">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                        Check In
                    </button>
                </form>
                @endif

                @if($appointment->status === 'checked_in')
                <form action="{{ route('appointments.status', $appointment) }}" method="POST" class="inline">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="in_consultation">
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white font-semibold rounded-xl hover:bg-purple-700 transition-colors">
                        Start Consultation
                    </button>
                </form>
                @endif

                @if($appointment->status === 'in_consultation')
                <form action="{{ route('appointments.status', $appointment) }}" method="POST" class="inline">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors">
                        Complete
                    </button>
                </form>
                @endif

                @if(in_array($appointment->status, ['confirmed', 'checked_in']))
                <form action="{{ route('appointments.status', $appointment) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this appointment?')">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="px-4 py-2 border border-red-300 text-red-600 font-semibold rounded-xl hover:bg-red-50 transition-colors">
                        Cancel Appointment
                    </button>
                </form>
                @endif

                @if($appointment->patient && $appointment->status === 'in_consultation')
                <a href="{{ route('emr.create', $appointment->patient) }}" class="px-4 py-2 bg-gray-800 text-white font-semibold rounded-xl hover:bg-gray-900 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Start EMR Visit
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Back Link --}}
        <div class="pt-4">
            <a href="{{ route('schedule') }}" class="text-blue-600 hover:text-blue-700 font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Schedule
            </a>
        </div>
    </div>
</div>
@endsection
