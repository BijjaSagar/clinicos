@extends('layouts.app')

@section('title', 'OPD Queue')

@section('content')
<div
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
    x-data="{
        walkinOpen: false,
        currentTime: '',
        init() {
            this.tick();
            setInterval(() => this.tick(), 1000);
        },
        tick() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        }
    }"
    x-init="init()"
>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">OPD Queue</h1>
            <p class="text-sm text-gray-500 mt-0.5">Outpatient department queue management</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Real-time clock --}}
            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-gray-100 rounded-lg">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-mono font-medium text-gray-700" x-text="currentTime"></span>
            </div>
            <button @click="walkinOpen = true"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-blue text-white text-sm font-medium rounded-lg hover:bg-brand-blue-dark transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Add Walk-in
            </button>
        </div>
    </div>

    {{-- Date Picker --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('opd.queue') }}" class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700">Date:</label>
            <input type="date" name="date" value="{{ $date }}"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors">
            <button type="submit" class="px-4 py-2 bg-brand-blue text-white text-sm font-medium rounded-lg hover:bg-brand-blue-dark transition-colors">
                Go
            </button>
            @if($date !== today()->toDateString())
                <a href="{{ route('opd.queue') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Today
                </a>
            @endif
        </form>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm text-center">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Total</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-amber-50 rounded-xl border border-amber-200 p-4 shadow-sm text-center">
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 mb-1">Waiting</p>
            <p class="text-2xl font-bold text-amber-700">{{ $stats['waiting'] }}</p>
        </div>
        <div class="bg-blue-50 rounded-xl border border-blue-200 p-4 shadow-sm text-center">
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 mb-1">In Progress</p>
            <p class="text-2xl font-bold text-blue-700">{{ $stats['in_progress'] }}</p>
        </div>
        <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-4 shadow-sm text-center">
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 mb-1">Completed</p>
            <p class="text-2xl font-bold text-emerald-700">{{ $stats['completed'] }}</p>
        </div>
        <div class="bg-red-50 rounded-xl border border-red-200 p-4 shadow-sm text-center">
            <p class="text-xs font-semibold uppercase tracking-wider text-red-600 mb-1">Cancelled</p>
            <p class="text-2xl font-bold text-red-700">{{ $stats['cancelled'] }}</p>
        </div>
    </div>

    {{-- Queue Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500 w-16">Token</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Patient</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Doctor</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Time</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Chief Complaint</th>
                        <th class="text-center px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="text-center px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($appointments as $index => $appt)
                    @php
                        $statusColors = [
                            'waiting'     => 'bg-amber-50 text-amber-700 border border-amber-200',
                            'confirmed'   => 'bg-amber-50 text-amber-700 border border-amber-200',
                            'in_progress' => 'bg-blue-50 text-blue-700 border border-blue-200',
                            'completed'   => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                            'cancelled'   => 'bg-red-50 text-red-600 border border-red-200',
                            'no_show'     => 'bg-gray-100 text-gray-500 border border-gray-200',
                            'done'        => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                        ];
                        $colorClass = $statusColors[$appt->queue_status] ?? $statusColors[$appt->status] ?? 'bg-gray-100 text-gray-500';
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center justify-center w-8 h-8 bg-brand-blue-light text-brand-blue font-bold text-sm rounded-lg">
                                {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-900">{{ $appt->patient->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $appt->patient->age ?? '—' }} yrs
                                @if($appt->patient->gender ?? null)
                                    · {{ ucfirst($appt->patient->gender) }}
                                @endif
                            </p>
                        </td>
                        <td class="px-4 py-4 text-gray-600">{{ $appt->doctor->name ?? '—' }}</td>
                        <td class="px-4 py-4 text-gray-600 whitespace-nowrap">
                            {{ $appt->appointment_time ? \Carbon\Carbon::parse($appt->appointment_time)->format('h:i A') : '—' }}
                        </td>
                        <td class="px-4 py-4 text-gray-600 max-w-xs">
                            <p class="truncate">{{ $appt->chief_complaint ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <form method="POST" action="{{ route('opd.status', $appt) }}">
                                @csrf
                                <select name="status" onchange="this.form.submit()"
                                    class="text-xs font-medium px-2.5 py-1.5 rounded-full border-0 cursor-pointer focus:ring-2 focus:ring-brand-blue/30 {{ $colorClass }}">
                                    <option value="confirmed"   {{ $appt->status === 'confirmed'   ? 'selected' : '' }}>Waiting</option>
                                    <option value="in_progress" {{ $appt->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed"   {{ $appt->status === 'completed'   ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled"   {{ $appt->status === 'cancelled'   ? 'selected' : '' }}>Cancelled</option>
                                    <option value="no_show"     {{ $appt->status === 'no_show'     ? 'selected' : '' }}>No Show</option>
                                </select>
                            </form>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if($appt->patient_id ?? null)
                                    <a href="{{ route('emr.create', $appt->patient_id) }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-white bg-brand-blue rounded-lg hover:bg-brand-blue-dark transition-colors whitespace-nowrap">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        Start
                                    </a>
                                    <a href="{{ route('patients.show', $appt->patient_id) }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors whitespace-nowrap">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <p class="text-gray-500 font-medium">No appointments for this date</p>
                                <p class="text-sm text-gray-400">Add a walk-in patient or check a different date.</p>
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
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="walkinOpen = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg" @click.stop>
            <div class="flex items-center justify-between p-6 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">Add Walk-in Patient</h2>
                <button @click="walkinOpen = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('opd.walkin') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient <span class="text-red-500">*</span></label>
                    <select name="patient_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors bg-white">
                        <option value="">Select patient...</option>
                        @foreach($patients ?? [] as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->name }} ({{ $patient->phone ?? 'No phone' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Doctor <span class="text-red-500">*</span></label>
                    <select name="doctor_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors bg-white">
                        <option value="">Select doctor...</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Chief Complaint</label>
                    <input type="text" name="chief_complaint" placeholder="e.g. Fever, headache"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                        <input type="date" name="appointment_date" value="{{ $date }}" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time <span class="text-red-500">*</span></label>
                        <input type="time" name="appointment_time" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors">
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" @click="walkinOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-brand-blue rounded-lg hover:bg-brand-blue-dark transition-colors shadow-sm">
                        Add to Queue
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
