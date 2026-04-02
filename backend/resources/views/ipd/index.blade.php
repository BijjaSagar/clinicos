@extends('layouts.app')

@section('title', 'In-Patient Department')
@section('breadcrumb', 'IPD')

@section('content')
<div x-data="{ statusFilter: '{{ request('status', 'all') }}', search: '{{ request('search', '') }}' }"
     class="p-4 sm:p-6 lg:p-8 space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">In-Patient Department</h1>
            </div>
            <p class="text-sm text-gray-500 ml-12">Admissions, ward management & patient care</p>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0">
            <a href="#"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Bed Map
            </a>
            <a href="{{ route('ipd.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Admit Patient
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Admitted --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-blue-500"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Total Admitted</p>
                    <p class="text-3xl font-extrabold text-gray-900 leading-none">{{ $stats['totalAdmitted'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Currently in wards</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 21v-6h4v6"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Available Beds --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-emerald-500"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Available Beds</p>
                    <p class="text-3xl font-extrabold text-emerald-600 leading-none">{{ $stats['availableBeds'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Ready for admission</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- ICU Beds Free --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-purple-500"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">ICU Beds Free</p>
                    <p class="text-3xl font-extrabold text-purple-600 leading-none">{{ $stats['icuBedsAvailable'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">ICU available</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Discharges Today --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 relative overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-1 rounded-l-2xl bg-gray-400"></div>
            <div class="flex items-start justify-between pl-3">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Discharges Today</p>
                    <p class="text-3xl font-extrabold text-gray-700 leading-none">{{ $stats['dischargesToday'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-2">Discharged today</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
        <form method="GET" action="{{ route('ipd.index') }}" class="flex flex-col lg:flex-row gap-3 items-start lg:items-center">

            {{-- Status Tabs --}}
            <div class="flex gap-1 bg-gray-100 rounded-xl p-1 flex-shrink-0">
                @foreach(['all' => 'All', 'admitted' => 'Admitted', 'discharged' => 'Discharged', 'transferred' => 'Transferred'] as $val => $label)
                <button type="button"
                    @click="statusFilter = '{{ $val }}'"
                    :class="statusFilter === '{{ $val }}'
                        ? 'bg-white text-gray-900 shadow-sm font-semibold'
                        : 'text-gray-500 hover:text-gray-700 font-medium'"
                    class="px-3.5 py-2 rounded-lg text-xs transition-all whitespace-nowrap">
                    {{ $label }}
                </button>
                @endforeach
            </div>
            <input type="hidden" name="status" x-bind:value="statusFilter">

            {{-- Search --}}
            <div class="flex-1 relative min-w-0 w-full lg:w-auto">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" x-model="search"
                    placeholder="Search patient name or IPD number…"
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    value="{{ request('search') }}">
            </div>

            {{-- Ward Filter --}}
            @if(isset($wards) && $wards->count())
            <select name="ward_id" class="px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent flex-shrink-0">
                <option value="">All Wards</option>
                @foreach($wards as $ward)
                <option value="{{ $ward->id }}" {{ request('ward_id') == $ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
                @endforeach
            </select>
            @endif

            <button type="submit"
                class="px-5 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-700 transition-colors flex-shrink-0">
                Apply
            </button>
        </form>
    </div>

    {{-- Admissions Table --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-bold text-gray-900">Current Admissions</h3>
                <span class="px-2.5 py-0.5 bg-blue-50 text-blue-700 text-xs font-semibold rounded-full">{{ $admissions->total() }} total</span>
            </div>
            <span class="text-xs text-gray-400 hidden sm:block">Showing {{ $admissions->firstItem() ?? 0 }}–{{ $admissions->lastItem() ?? 0 }} of {{ $admissions->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead>
                    <tr class="bg-gray-50/70 border-b border-gray-100">
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ward / Bed</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Doctor</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Admitted On</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Diagnosis</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($admissions as $admission)
                    @php
                        $statusConfig = [
                            'admitted'    => ['label' => 'Admitted',    'class' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
                            'discharged'  => ['label' => 'Discharged',  'class' => 'bg-gray-100 text-gray-600 ring-1 ring-gray-200'],
                            'transferred' => ['label' => 'Transferred', 'class' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'],
                            'critical'    => ['label' => 'Critical',    'class' => 'bg-red-50 text-red-700 ring-1 ring-red-200'],
                        ];
                        $sc = $statusConfig[$admission->status] ?? $statusConfig['admitted'];
                        $avatarColors = ['#2563EB','#7C3AED','#059669','#DC2626','#D97706','#0891B2'];
                        $avatarBg = $avatarColors[ord($admission->patient->name[0] ?? 'P') % count($avatarColors)];
                    @endphp
                    <tr class="hover:bg-blue-50/30 transition-colors group">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm"
                                     style="background: {{ $avatarBg }};">
                                    {{ strtoupper(substr($admission->patient->name ?? 'P', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 group-hover:text-blue-700 transition-colors">
                                        {{ $admission->patient->name ?? '—' }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        <span class="font-mono">{{ $admission->admission_number }}</span>
                                        @if($admission->patient->phone ?? null)
                                        · {{ $admission->patient->phone }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-lg ring-1 ring-indigo-200">
                                {{ $admission->ward->name ?? '—' }}
                            </span>
                            <p class="text-xs text-gray-400 mt-1">Bed {{ $admission->bed->bed_number ?? '—' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-sm text-gray-800 font-medium">{{ $admission->primaryDoctor->name ?? '—' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-sm text-gray-800">
                                {{ $admission->admission_date ? \Carbon\Carbon::parse($admission->admission_date)->format('d M Y') : '—' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $admission->admission_date ? \Carbon\Carbon::parse($admission->admission_date)->diffForHumans() : '' }}
                            </p>
                        </td>
                        <td class="px-5 py-4 max-w-[200px]">
                            <p class="text-sm text-gray-700 truncate" title="{{ $admission->diagnosis_at_admission }}">
                                {{ $admission->diagnosis_at_admission ?? '—' }}
                            </p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $sc['class'] }}">
                                @if($admission->status === 'admitted')
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                @endif
                                {{ $sc['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-1">
                                <a href="{{ route('ipd.show', $admission) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View
                                </a>
                                <a href="{{ route('ipd.print-card', $admission) }}" target="_blank"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors" title="Print Card">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                </a>
                                @if($admission->status === 'admitted')
                                <a href="{{ route('ipd.show', $admission) }}#discharge"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Discharge">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-20 text-center">
                            <div class="flex flex-col items-center gap-4 max-w-sm mx-auto">
                                <div class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-base font-bold text-gray-700">No admissions found</p>
                                    <p class="text-sm text-gray-400 mt-1">Try adjusting your filters or admit a new patient</p>
                                </div>
                                <a href="{{ route('ipd.create') }}"
                                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Admit First Patient
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($admissions->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $admissions->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
