@extends('layouts.app')

@section('title', 'Schedule')
@section('breadcrumb', 'Today\'s Schedule')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Today's Schedule</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ now()->format('l, d F Y') }}</p>
        </div>
        <a href="{{ route('appointments.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Book Appointment
        </a>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 font-medium">Total Today</p>
            <p class="text-2xl font-extrabold text-gray-900 font-display mt-1">{{ $appointments->count() ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 font-medium">Checked In</p>
            <p class="text-2xl font-extrabold text-green-600 font-display mt-1">{{ $appointments->where('status', 'checked_in')->count() ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 font-medium">Waiting</p>
            <p class="text-2xl font-extrabold text-amber-600 font-display mt-1">{{ $appointments->where('status', 'waiting')->count() ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 font-medium">Completed</p>
            <p class="text-2xl font-extrabold text-blue-600 font-display mt-1">{{ $appointments->where('status', 'completed')->count() ?? 0 }}</p>
        </div>
    </div>

    {{-- Appointments List --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-900">Appointments</h3>
            <div class="flex gap-2">
                <button class="px-3 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 rounded-lg">All</button>
                <button class="px-3 py-1.5 text-xs font-semibold text-gray-500 hover:bg-gray-50 rounded-lg">Upcoming</button>
                <button class="px-3 py-1.5 text-xs font-semibold text-gray-500 hover:bg-gray-50 rounded-lg">Completed</button>
            </div>
        </div>

        <div class="divide-y divide-gray-200">
            @forelse($appointments as $appointment)
            <div class="px-5 py-4 flex items-center gap-4 hover:bg-gray-50 transition-colors">
                {{-- Time --}}
                <div class="w-20 text-center">
                    <p class="text-lg font-bold text-gray-900 font-display">{{ \Carbon\Carbon::parse($appointment->scheduled_at)->format('h:i') }}</p>
                    <p class="text-xs text-gray-400 uppercase">{{ \Carbon\Carbon::parse($appointment->scheduled_at)->format('A') }}</p>
                </div>

                {{-- Divider --}}
                <div class="w-px h-12 bg-gray-200"></div>

                {{-- Patient Info --}}
                <div class="flex-1 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                         style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                        {{ strtoupper(substr($appointment->patient->name ?? 'P', 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $appointment->patient->name ?? 'Unknown' }}</p>
                        <p class="text-sm text-gray-500">{{ $appointment->service->name ?? 'Consultation' }} · {{ $appointment->duration ?? 30 }} min</p>
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    @php
                        $statusClasses = [
                            'scheduled' => 'bg-gray-100 text-gray-600',
                            'confirmed' => 'bg-blue-100 text-blue-700',
                            'checked_in' => 'bg-amber-100 text-amber-700',
                            'in_progress' => 'bg-purple-100 text-purple-700',
                            'completed' => 'bg-green-100 text-green-700',
                            'no_show' => 'bg-red-100 text-red-700',
                            'cancelled' => 'bg-gray-100 text-gray-500',
                        ];
                    @endphp
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$appointment->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ str_replace('_', ' ', ucfirst($appointment->status)) }}
                    </span>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2" x-data="{ open: false }">
                    {{-- Quick Action Button --}}
                    @if($appointment->status === 'confirmed' || $appointment->status === 'booked')
                    <form action="{{ route('appointments.status', $appointment) }}" method="POST" class="inline">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="checked_in">
                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">
                            Check In
                        </button>
                    </form>
                    @elseif($appointment->status === 'checked_in')
                    <form action="{{ route('appointments.status', $appointment) }}" method="POST" class="inline">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="in_consultation">
                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded-lg">
                            Start Consult
                        </button>
                    </form>
                    @elseif($appointment->status === 'in_consultation')
                    <form action="{{ route('appointments.status', $appointment) }}" method="POST" class="inline">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                            Complete
                        </button>
                    </form>
                    @endif

                    {{-- Dropdown Menu --}}
                    <div class="relative">
                        <button @click="open = !open" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                            </svg>
                        </button>

                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 top-full mt-1 w-48 bg-white rounded-xl shadow-xl border border-gray-200 py-1 z-[100]"
                             style="position: fixed; transform: translateX(-75%);"
                             x-init="$watch('open', value => {
                                 if (value) {
                                     const btn = $el.previousElementSibling;
                                     const rect = btn.getBoundingClientRect();
                                     $el.style.top = (rect.bottom + 4) + 'px';
                                     $el.style.left = (rect.right - 192) + 'px';
                                     $el.style.transform = 'none';
                                 }
                             })">
                            
                            {{-- View Details --}}
                            <a href="{{ route('appointments.show', $appointment) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                View Details
                            </a>

                            {{-- View Patient --}}
                            <a href="{{ route('patients.show', $appointment->patient_id) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                View Patient
                            </a>

                            @if($appointment->status !== 'completed' && $appointment->status !== 'cancelled')
                            <div class="border-t border-gray-100 my-1"></div>

                            {{-- Status Changes --}}
                            @if($appointment->status === 'confirmed' || $appointment->status === 'booked')
                            <form action="{{ route('appointments.status', $appointment) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="checked_in">
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-green-600 hover:bg-green-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Check In
                                </button>
                            </form>
                            @endif

                            @if($appointment->status === 'checked_in')
                            <form action="{{ route('appointments.status', $appointment) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="in_consultation">
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-purple-600 hover:bg-purple-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    Start Consultation
                                </button>
                            </form>
                            @endif

                            <div class="border-t border-gray-100 my-1"></div>

                            {{-- Mark No Show --}}
                            <form action="{{ route('appointments.status', $appointment) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="no_show">
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-amber-600 hover:bg-amber-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Mark No Show
                                </button>
                            </form>

                            {{-- Cancel --}}
                            <form action="{{ route('appointments.status', $appointment) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment?')">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Cancel Appointment
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-5 py-12 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p>No appointments scheduled for today</p>
                <a href="{{ route('appointments.create') }}" class="inline-flex items-center gap-2 mt-4 text-blue-600 hover:text-blue-700 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Book an appointment
                </a>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
