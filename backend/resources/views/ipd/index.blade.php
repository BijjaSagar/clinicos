@extends('layouts.app')

@section('title', 'IPD Management')
@section('breadcrumb', 'IPD Management')

@section('content')
<div x-data="{ statusFilter: '{{ request('status', 'all') }}', search: '{{ request('search', '') }}' }" class="p-4 sm:p-5 lg:p-7 space-y-5">

    {{-- Page Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 font-display">IPD Management</h1>
            <p class="text-sm text-gray-500 mt-0.5">In-Patient Department — Admissions & Ward Management</p>
        </div>
        <a href="{{ route('ipd.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:shadow-lg hover:scale-[1.02]"
           style="background:linear-gradient(135deg,#1447E6,#0891B2);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Admit Patient
        </a>
    </div>

    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium" style="background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5">
            <p class="text-xs font-medium text-gray-400 mb-1.5">Total Admitted</p>
            <p class="font-display font-extrabold text-2xl sm:text-3xl text-gray-900 leading-none">{{ $stats['totalAdmitted'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">Currently in wards</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5">
            <p class="text-xs font-medium text-gray-400 mb-1.5">Available Beds</p>
            <p class="font-display font-extrabold text-2xl sm:text-3xl leading-none" style="color:#059669;">{{ $stats['availableBeds'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">Ready for admission</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5">
            <p class="text-xs font-medium text-gray-400 mb-1.5">Discharges Today</p>
            <p class="font-display font-extrabold text-2xl sm:text-3xl text-gray-900 leading-none">{{ $stats['dischargesToday'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">Discharged today</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5">
            <p class="text-xs font-medium text-gray-400 mb-1.5">ICU Beds Free</p>
            <p class="font-display font-extrabold text-2xl sm:text-3xl leading-none" style="color:#d97706;">{{ $stats['icuBedsAvailable'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">ICU available</p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <form method="GET" action="{{ route('ipd.index') }}" class="flex flex-col sm:flex-row gap-3">
            {{-- Status Filter --}}
            <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
                @foreach(['all' => 'All', 'admitted' => 'Admitted', 'discharged' => 'Discharged', 'transferred' => 'Transferred'] as $val => $label)
                <button type="button"
                    @click="statusFilter = '{{ $val }}'"
                    :class="statusFilter === '{{ $val }}' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all whitespace-nowrap">
                    {{ $label }}
                </button>
                @endforeach
            </div>
            <input type="hidden" name="status" x-bind:value="statusFilter">

            {{-- Ward Filter --}}
            @if(isset($wards) && $wards->count())
            <select name="ward_id" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Wards</option>
                @foreach($wards as $ward)
                <option value="{{ $ward->id }}" {{ request('ward_id') == $ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
                @endforeach
            </select>
            @endif

            {{-- Search --}}
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" x-model="search" placeholder="Search patient name, admission no…"
                    class="w-full pl-9 pr-4 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value="{{ request('search') }}">
            </div>

            <button type="submit" class="px-4 py-1.5 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition-colors">
                Filter
            </button>
        </form>
    </div>

    {{-- Admissions Table --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">Current Admissions</h3>
            <span class="text-xs text-gray-400">{{ $admissions->total() }} total</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Patient</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Ward / Bed</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Admission Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Primary Doctor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Diagnosis</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($admissions as $admission)
                    @php
                        $statusMap = [
                            'admitted'    => ['label' => 'Admitted',    'bg' => '#ecfdf5', 'color' => '#059669'],
                            'discharged'  => ['label' => 'Discharged',  'bg' => '#f1f5f9', 'color' => '#64748b'],
                            'transferred' => ['label' => 'Transferred', 'bg' => '#fff7ed', 'color' => '#d97706'],
                            'critical'    => ['label' => 'Critical',    'bg' => '#fff1f2', 'color' => '#dc2626'],
                        ];
                        $s = $statusMap[$admission->status] ?? $statusMap['admitted'];
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                                     style="background:linear-gradient(135deg,#1447E6,#0891B2);">
                                    {{ strtoupper(substr($admission->patient->name ?? 'P', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $admission->patient->name ?? '—' }}</p>
                                    <p class="text-xs text-gray-400">{{ $admission->admission_number }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-900">{{ $admission->ward->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400">Bed: {{ $admission->bed->bed_number ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $admission->admission_date ? \Carbon\Carbon::parse($admission->admission_date)->format('d M Y') : '—' }}
                            <p class="text-xs text-gray-400">
                                {{ $admission->admission_date ? \Carbon\Carbon::parse($admission->admission_date)->diffForHumans() : '' }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $admission->primaryDoctor->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-700 max-w-[200px] truncate" title="{{ $admission->diagnosis_at_admission }}">
                                {{ $admission->diagnosis_at_admission ?? '—' }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                  style="background:{{ $s['bg'] }};color:{{ $s['color'] }};">
                                {{ $s['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('ipd.show', $admission) }}"
                                   class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('ipd.print-card', $admission) }}" target="_blank"
                                   class="p-1.5 rounded-lg text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors" title="Print Card">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                </a>
                                @if($admission->status === 'admitted')
                                <a href="{{ route('ipd.show', $admission) }}#discharge"
                                   class="p-1.5 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 transition-colors" title="Discharge">
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
                        <td colspan="7" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-500">No admissions found</p>
                                <p class="text-xs text-gray-400">Try adjusting your filters or admit a new patient</p>
                                <a href="{{ route('ipd.create') }}" class="text-sm font-semibold" style="color:#1447E6;">Admit Patient →</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($admissions->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $admissions->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
