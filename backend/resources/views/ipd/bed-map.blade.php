@extends('layouts.app')

@section('title', 'Bed Map')
@section('breadcrumb', 'IPD / Bed Map')

@section('content')
<div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Bed Map</h1>
            <p class="text-sm text-gray-500 mt-0.5">Live occupancy across all wards · Updated {{ now()->format('h:i A') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="location.reload()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
            <a href="{{ route('ipd.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                ← Back to IPD
            </a>
        </div>
    </div>

    {{-- Legend + Summary Stats --}}
    @php
        $totalBeds     = $wards->sum(fn($w) => $w->beds->count());
        $occupiedBeds  = $wards->sum(fn($w) => $w->beds->where('status', 'occupied')->count());
        $availableBeds = $wards->sum(fn($w) => $w->beds->where('status', 'available')->count());
        $cleaningBeds  = $wards->sum(fn($w) => $w->beds->whereNotIn('status', ['occupied','available'])->count());
        $occupancyPct  = $totalBeds > 0 ? round($occupiedBeds / $totalBeds * 100) : 0;
    @endphp

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $totalBeds }}</p>
            <p class="text-xs text-gray-400 mt-1">Total Beds</p>
        </div>
        <div class="bg-white rounded-xl border border-red-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $occupiedBeds }}</p>
            <p class="text-xs text-gray-400 mt-1">Occupied</p>
        </div>
        <div class="bg-white rounded-xl border border-green-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $availableBeds }}</p>
            <p class="text-xs text-gray-400 mt-1">Available</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-700">{{ $occupancyPct }}%</p>
            <p class="text-xs text-gray-400 mt-1">Occupancy</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-4 text-xs font-medium text-gray-600">
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded bg-red-400 block"></span> Occupied
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded bg-green-400 block"></span> Available
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded bg-amber-400 block"></span> Cleaning / Reserved
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded bg-gray-300 block"></span> Maintenance
        </div>
    </div>

    {{-- Ward Sections --}}
    @forelse($wards as $ward)
    @php
        $wAvail    = $ward->beds->where('status', 'available')->count();
        $wOccupied = $ward->beds->where('status', 'occupied')->count();
        $wTotal    = $ward->beds->count();
        $wPct      = $wTotal > 0 ? round($wOccupied / $wTotal * 100) : 0;
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">

        {{-- Ward Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50/60">
            <div>
                <h2 class="text-sm font-bold text-gray-900">{{ $ward->name }}</h2>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ ucwords(str_replace('_', ' ', $ward->ward_type ?? 'General')) }}
                    @if($ward->floor) · Floor {{ $ward->floor }}@endif
                    · {{ $wTotal }} beds
                </p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right text-xs text-gray-500">
                    <span class="font-semibold text-red-600">{{ $wOccupied }}</span> occupied ·
                    <span class="font-semibold text-green-600">{{ $wAvail }}</span> free
                </div>
                <div class="w-24">
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all
                            {{ $wPct >= 90 ? 'bg-red-500' : ($wPct >= 70 ? 'bg-amber-500' : 'bg-green-500') }}"
                             style="width:{{ $wPct }}%"></div>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-0.5 text-right">{{ $wPct }}%</p>
                </div>
            </div>
        </div>

        {{-- Beds Grid --}}
        @if($ward->beds->isEmpty())
        <div class="py-10 text-center text-sm text-gray-400">No beds configured for this ward.</div>
        @else
        <div class="p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3">
            @foreach($ward->beds->sortBy('bed_number') as $bed)
            @php
                $admission = $bed->currentAdmission ?? null;
                $patient   = $admission?->patient ?? null;
                $daysAdmitted = $admission && $admission->admission_date
                    ? \Carbon\Carbon::parse($admission->admission_date)->diffInDays(now())
                    : null;

                [$bgClass, $borderClass, $textClass, $dotClass] = match($bed->status) {
                    'available'  => ['bg-green-50',  'border-green-300',  'text-green-800',  'bg-green-400'],
                    'occupied'   => ['bg-red-50',    'border-red-300',    'text-red-800',    'bg-red-400'],
                    'cleaning'   => ['bg-amber-50',  'border-amber-300',  'text-amber-800',  'bg-amber-400'],
                    'reserved'   => ['bg-amber-50',  'border-amber-300',  'text-amber-800',  'bg-amber-400'],
                    default      => ['bg-gray-50',   'border-gray-200',   'text-gray-500',   'bg-gray-300'],
                };
            @endphp
            <div class="rounded-xl border {{ $bgClass }} {{ $borderClass }} p-3 flex flex-col gap-1 min-h-[88px] relative
                        {{ $patient ? 'cursor-pointer hover:shadow-md transition-shadow' : '' }}"
                 @if($patient)onclick="window.location='{{ route('ipd.show', $admission->id) }}'"@endif
                 title="{{ $patient?->name ?? ucfirst($bed->status) }}">

                {{-- Status dot + Bed number --}}
                <div class="flex items-center justify-between">
                    <span class="font-bold text-sm {{ $textClass }}">{{ $bed->bed_number }}</span>
                    <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
                </div>

                {{-- Bed type --}}
                <div class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">
                    {{ $bed->bed_type ?? 'Standard' }}
                </div>

                @if($patient)
                {{-- Patient info --}}
                <div class="mt-auto">
                    <p class="text-xs font-semibold {{ $textClass }} truncate leading-tight" title="{{ $patient->name }}">
                        {{ $patient->name }}
                    </p>
                    @if($daysAdmitted !== null)
                    <p class="text-[10px] text-gray-400 mt-0.5">
                        Day {{ $daysAdmitted + 1 }}
                    </p>
                    @endif
                </div>
                @else
                <div class="mt-auto text-[10px] uppercase tracking-wide {{ $textClass }} opacity-70">
                    {{ ucfirst($bed->status) }}
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @empty
    <div class="bg-white rounded-2xl border border-gray-100 py-20 text-center">
        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <p class="text-sm text-gray-500">No wards configured yet.</p>
        <a href="{{ route('hospital-settings.index') }}" class="mt-3 inline-block text-sm text-blue-600 hover:underline">
            Add wards in Hospital Settings →
        </a>
    </div>
    @endforelse

</div>
@endsection
