@extends('layouts.app')

@section('title', 'Bed Map')

@section('breadcrumb', 'IPD / Bed Map')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Bed Map</h1>
            <p class="text-sm text-gray-500 mt-0.5">Live occupancy across all wards</p>
        </div>
        <a href="{{ route('ipd.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
            ← Back to IPD
        </a>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap gap-4 text-sm">
        <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-green-100 border border-green-300"></span> Available</div>
        <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-red-100 border border-red-300"></span> Occupied</div>
        <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-yellow-100 border border-yellow-300"></span> Cleaning / Reserved</div>
    </div>

    @forelse($wards as $ward)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        {{-- Ward Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50">
            <div>
                <h2 class="text-sm font-bold text-gray-900">{{ $ward->name }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ ucwords(str_replace('_', ' ', $ward->ward_type ?? '')) }}
                    @if($ward->floor) · Floor: {{ $ward->floor }}@endif
                </p>
            </div>
            <div class="text-right">
                @php
                    $available = $ward->beds->where('status', 'available')->count();
                    $total     = $ward->beds->count();
                    $occupied  = $ward->beds->where('status', 'occupied')->count();
                @endphp
                <p class="text-xs text-gray-500">{{ $occupied }}/{{ $total }} occupied</p>
                <div class="w-32 h-1.5 bg-gray-200 rounded-full mt-1 overflow-hidden">
                    <div class="h-full bg-red-400 rounded-full" style="width:{{ $total > 0 ? round($occupied/$total*100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        {{-- Beds Grid --}}
        @if($ward->beds->isEmpty())
        <div class="py-8 text-center text-sm text-gray-400">No beds configured for this ward.</div>
        @else
        <div class="p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($ward->beds as $bed)
            @php
                $admission = $bed->currentAdmission;
                $patient   = $admission?->patient;
                $statusColor = match($bed->status) {
                    'available' => 'bg-green-50 border-green-200 text-green-700',
                    'occupied'  => 'bg-red-50 border-red-200 text-red-700',
                    'cleaning'  => 'bg-yellow-50 border-yellow-200 text-yellow-700',
                    default     => 'bg-gray-50 border-gray-200 text-gray-500',
                };
            @endphp
            <div class="rounded-lg border p-3 text-xs {{ $statusColor }} flex flex-col gap-1"
                 title="{{ $patient?->name ?? ucfirst($bed->status) }}">
                <div class="font-bold text-sm">{{ $bed->bed_number }}</div>
                <div class="text-[10px] uppercase tracking-wide opacity-70">{{ $bed->bed_type ?? 'Standard' }}</div>
                @if($patient)
                <div class="font-semibold truncate mt-1" title="{{ $patient->name }}">{{ $patient->name }}</div>
                <div class="opacity-70">
                    Since {{ $admission->admission_date ? \Carbon\Carbon::parse($admission->admission_date)->format('d M') : '—' }}
                </div>
                @if($admission)
                <a href="{{ route('ipd.show', $admission->id) }}"
                   class="mt-1 inline-block text-center text-[10px] font-semibold underline">View</a>
                @endif
                @else
                <div class="mt-1 text-[10px] uppercase opacity-60">{{ ucfirst($bed->status) }}</div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @empty
    <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
        <p class="text-sm text-gray-500">No wards configured yet.</p>
        <a href="{{ route('hospital-settings.index') }}" class="mt-3 inline-block text-sm text-blue-600 hover:underline">
            Add wards in Hospital Settings →
        </a>
    </div>
    @endforelse

</div>
@endsection
