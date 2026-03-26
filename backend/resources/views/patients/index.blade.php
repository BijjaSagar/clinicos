@extends('layouts.app')

@section('title', 'Patients')
@section('breadcrumb', 'Patients')

@section('content')
@php
$demoPatients = [
    ['id'=>1,'name'=>'Priya Mehta',      'age'=>34,'gender'=>'F','blood'=>'B+','last_visit'=>'26 Mar 2026','visit_type'=>'Follow-up','abha'=>true, 'conditions'=>['Acne Grade 3','Melasma'],           'initials'=>'PM','gradient'=>'#f59e0b,#ef4444','unread'=>2],
    ['id'=>2,'name'=>'Rajesh Kumar',     'age'=>47,'gender'=>'M','blood'=>'O+','last_visit'=>'26 Mar 2026','visit_type'=>'Procedure','abha'=>true, 'conditions'=>['LASER Hair Removal','Androgenic Alopecia'],'initials'=>'RK','gradient'=>'#0891b2,#6366f1','unread'=>0],
    ['id'=>3,'name'=>'Ananya Patil',     'age'=>28,'gender'=>'F','blood'=>'A+','last_visit'=>'26 Mar 2026','visit_type'=>'New Patient','abha'=>false,'conditions'=>['Psoriasis'],                  'initials'=>'AP','gradient'=>'#8b5cf6,#ec4899','unread'=>0],
    ['id'=>4,'name'=>'Vikram Shah',      'age'=>39,'gender'=>'M','blood'=>'AB+','last_visit'=>'25 Mar 2026','visit_type'=>'Procedure','abha'=>true, 'conditions'=>['Alopecia','PRP Therapy'],      'initials'=>'VS','gradient'=>'#059669,#0891b2','unread'=>1],
    ['id'=>5,'name'=>'Suresh Deshpande','age'=>52,'gender'=>'M','blood'=>'O-','last_visit'=>'26 Mar 2026','visit_type'=>'Consultation','abha'=>false,'conditions'=>['Seborrheic Dermatitis'],     'initials'=>'SD','gradient'=>'#6366f1,#8b5cf6','unread'=>0],
    ['id'=>6,'name'=>'Neha Joshi',       'age'=>31,'gender'=>'F','blood'=>'B-','last_visit'=>'25 Mar 2026','visit_type'=>'Follow-up','abha'=>true, 'conditions'=>['Chemical Peel Recovery'],      'initials'=>'NJ','gradient'=>'#f97316,#fbbf24','unread'=>0],
    ['id'=>7,'name'=>'Arun Nair',        'age'=>44,'gender'=>'M','blood'=>'A-','last_visit'=>'24 Mar 2026','visit_type'=>'Consultation','abha'=>true,'conditions'=>['Atopic Dermatitis','Eczema'],'initials'=>'AN','gradient'=>'#1447e6,#0891b2','unread'=>0],
    ['id'=>8,'name'=>'Kavitha Reddy',    'age'=>26,'gender'=>'F','blood'=>'O+','last_visit'=>'22 Mar 2026','visit_type'=>'New Patient','abha'=>false,'conditions'=>['Vitiligo'],                   'initials'=>'KR','gradient'=>'#ec4899,#f43f5e','unread'=>0],
    ['id'=>9,'name'=>'Deepak Mishra',    'age'=>58,'gender'=>'M','blood'=>'B+','last_visit'=>'20 Mar 2026','visit_type'=>'Follow-up','abha'=>true, 'conditions'=>['Rosacea','Dry Skin'],           'initials'=>'DM','gradient'=>'#0d9488,#0891b2','unread'=>0],
    ['id'=>10,'name'=>'Sunita Iyer',     'age'=>41,'gender'=>'F','blood'=>'AB-','last_visit'=>'19 Mar 2026','visit_type'=>'Consultation','abha'=>true,'conditions'=>['Hyperpigmentation'],         'initials'=>'SI','gradient'=>'#7c3aed,#a855f7','unread'=>3],
    ['id'=>11,'name'=>'Mohan Gupta',     'age'=>35,'gender'=>'M','blood'=>'O+','last_visit'=>'18 Mar 2026','visit_type'=>'Procedure','abha'=>false,'conditions'=>['Wart Removal'],                 'initials'=>'MG','gradient'=>'#16a34a,#059669','unread'=>0],
    ['id'=>12,'name'=>'Pooja Banerjee',  'age'=>29,'gender'=>'F','blood'=>'A+','last_visit'=>'15 Mar 2026','visit_type'=>'Follow-up','abha'=>true, 'conditions'=>['Fungal Infection','Tinea'],     'initials'=>'PB','gradient'=>'#db2777,#ec4899','unread'=>0],
];
@endphp

<div class="p-6 space-y-5" x-data="{ activeFilter: 'all' }">

    {{-- ── TOP BAR ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        <div>
            <h1 class="font-display font-bold text-2xl text-gray-900">Patients</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage your patient records and histories</p>
        </div>
        <div class="sm:ml-auto flex items-center gap-3">
            <a href="{{ route('patients.create') }}"
               class="flex items-center gap-2 text-white font-semibold text-sm px-4 py-2.5 rounded-xl hover:opacity-90 transition-opacity"
               style="background:#1447E6;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add New Patient
            </a>
        </div>
    </div>

    {{-- ── SEARCH + STATS ── --}}
    <div class="flex flex-col lg:flex-row gap-4">
        {{-- Search --}}
        <div class="flex-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input type="text" placeholder="Search by name, phone, ABHA ID, condition..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:border-transparent"
                   style="--tw-ring-color:#1447E6;"/>
        </div>
        {{-- Stats Pills --}}
        <div class="flex items-center gap-2 flex-shrink-0">
            <div class="flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-200 rounded-xl">
                <span class="text-xs text-gray-500">Total</span>
                <span class="text-sm font-bold text-gray-900">{{ $stats['total'] ?? 248 }}</span>
            </div>
            <div class="flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-200 rounded-xl">
                <span class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></span>
                <span class="text-xs text-gray-500">New this month</span>
                <span class="text-sm font-bold text-gray-900">{{ $stats['new_month'] ?? 12 }}</span>
            </div>
            <div class="flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-200 rounded-xl">
                <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                <span class="text-xs text-gray-500">Active cases</span>
                <span class="text-sm font-bold text-gray-900">{{ $stats['active'] ?? 34 }}</span>
            </div>
        </div>
    </div>

    {{-- ── FILTER CHIPS ── --}}
    <div class="flex items-center gap-2 flex-wrap">
        <button @click="activeFilter='all'"
                :class="activeFilter==='all' ? 'border-transparent text-white' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                :style="activeFilter==='all' ? 'background:#1447E6;' : ''"
                class="px-4 py-1.5 rounded-full text-xs font-semibold border transition-all">All</button>
        <button @click="activeFilter='today'"
                :class="activeFilter==='today' ? 'border-transparent text-white' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                :style="activeFilter==='today' ? 'background:#1447E6;' : ''"
                class="px-4 py-1.5 rounded-full text-xs font-semibold border transition-all">Today</button>
        <button @click="activeFilter='recent'"
                :class="activeFilter==='recent' ? 'border-transparent text-white' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                :style="activeFilter==='recent' ? 'background:#1447E6;' : ''"
                class="px-4 py-1.5 rounded-full text-xs font-semibold border transition-all">Recent</button>
        <button @click="activeFilter='flagged'"
                :class="activeFilter==='flagged' ? 'border-transparent text-white' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                :style="activeFilter==='flagged' ? 'background:#1447E6;' : ''"
                class="px-4 py-1.5 rounded-full text-xs font-semibold border transition-all">Flagged</button>
    </div>

    {{-- ── PATIENT GRID ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($patients ?? $demoPatients as $patient)
        <a href="{{ route('patients.show', $patient['id'] ?? $patient->id) }}"
           class="bg-white border border-gray-200 rounded-xl p-5 hover:shadow-md hover:border-blue-200 transition-all group block">
            <div class="flex items-start gap-3">
                {{-- Avatar --}}
                <div class="relative flex-shrink-0">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-base font-display"
                         style="background:linear-gradient(135deg,{{ $patient['gradient'] ?? $patient->gradient ?? '#1447E6,#0891B2' }})">
                        {{ $patient['initials'] ?? strtoupper(substr($patient->name ?? '', 0, 2)) }}
                    </div>
                    @if(($patient['unread'] ?? 0) > 0)
                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                        {{ $patient['unread'] ?? $patient->unread_count ?? 0 }}
                    </span>
                    @endif
                </div>
                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="font-display font-bold text-sm text-gray-900 truncate group-hover:text-blue-700 transition-colors">
                                {{ $patient['name'] ?? $patient->name }}
                            </h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $patient['age'] ?? $patient->age }}y ·
                                {{ $patient['gender'] ?? $patient->gender }} ·
                                {{ $patient['blood'] ?? $patient->blood_group }}
                            </p>
                        </div>
                        @if($patient['abha'] ?? $patient->abha_id)
                        <span class="flex-shrink-0 text-xs font-semibold px-1.5 py-0.5 rounded"
                              style="background:#eff3ff;color:#1447E6;">ABHA</span>
                        @endif
                    </div>

                    {{-- Last Visit --}}
                    <div class="flex items-center gap-1.5 mt-2">
                        <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-xs text-gray-500">{{ $patient['last_visit'] ?? $patient->last_visit }}</span>
                        <span class="text-xs text-gray-300">·</span>
                        <span class="text-xs font-medium" style="color:#0891B2;">{{ $patient['visit_type'] ?? $patient->last_visit_type }}</span>
                    </div>

                    {{-- Condition Chips --}}
                    @php $conditions = $patient['conditions'] ?? ($patient->conditions ?? []); @endphp
                    @if(count($conditions) > 0)
                    <div class="flex flex-wrap gap-1 mt-2">
                        @foreach(array_slice($conditions, 0, 2) as $cond)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $cond }}</span>
                        @endforeach
                        @if(count($conditions) > 2)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-400">+{{ count($conditions)-2 }}</span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </a>
        @empty
        {{-- Empty State --}}
        <div class="col-span-full flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-700 mb-1">No patients found</h3>
            <p class="text-sm text-gray-400 mb-6">Add your first patient to get started.</p>
            <a href="{{ route('patients.create') }}"
               class="flex items-center gap-2 text-white font-semibold text-sm px-5 py-2.5 rounded-xl"
               style="background:#1447E6;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add New Patient
            </a>
        </div>
        @endforelse
    </div>

    {{-- ── PAGINATION ── --}}
    @if(isset($patients) && method_exists($patients, 'links'))
    <div class="flex justify-center pt-2">
        {{ $patients->links() }}
    </div>
    @endif

</div>
@endsection
