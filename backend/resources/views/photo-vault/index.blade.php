@extends('layouts.app')

@section('title', 'Photo Vault')
@section('breadcrumb', 'Photo Vault')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Photo Vault</h1>
            <p class="text-sm text-gray-500 mt-0.5">Secure clinical photography storage</p>
        </div>
        <button class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors" onclick="alert('Photo upload requires patient context. Go to a patient profile to upload photos.')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Upload Photos
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium">Total Photos</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Across all patients</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium">This Month</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['this_month'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">New uploads</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium">Storage Used</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['storage_mb'] ?? 0 }} MB</p>
            <p class="text-xs text-gray-400 mt-1">of 5 GB</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium">Before/After Sets</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">{{ $stats['before_after_sets'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Comparison sets</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Patient</label>
                <select name="patient_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[180px]">
                    <option value="">All Patients</option>
                    @foreach($patientsWithPhotos ?? [] as $patient)
                    <option value="{{ $patient->id }}" {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                        {{ $patient->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Photo Type</label>
                <select name="type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Types</option>
                    <option value="before" {{ request('type') === 'before' ? 'selected' : '' }}>Before</option>
                    <option value="after" {{ request('type') === 'after' ? 'selected' : '' }}>After</option>
                    <option value="progress" {{ request('type') === 'progress' ? 'selected' : '' }}>Progress</option>
                    <option value="clinical" {{ request('type') === 'clinical' ? 'selected' : '' }}>Clinical</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Body Region</label>
                <select name="region" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Regions</option>
                    @foreach($bodyRegions ?? [] as $region)
                    <option value="{{ $region }}" {{ request('region') === $region ? 'selected' : '' }}>
                        {{ ucfirst($region) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Filter
                </button>
                <a href="{{ route('photo-vault.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Clear
                </a>
            </div>
        </form>
    </div>

    @if(isset($photos) && (is_object($photos) ? $photos->count() : count($photos)) > 0)
    {{-- Photo Grid --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Photos</h3>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($photos as $photo)
                @php
                    $photoUrl = route('patients.view-photo', ['patient' => $photo->patient_id, 'photo' => $photo->id]);
                @endphp
                <div class="group relative bg-gray-100 rounded-xl overflow-hidden aspect-square" x-data="{ showDelete: false }">
                    {{-- Actual Photo (using secure route) --}}
                    <a href="{{ $photoUrl }}" target="_blank" class="block w-full h-full">
                        <img src="{{ $photoUrl }}" 
                             alt="{{ $photo->body_region ?? 'Clinical photo' }}"
                             class="absolute inset-0 w-full h-full object-cover"
                             loading="lazy"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        {{-- Fallback placeholder --}}
                        <div class="absolute inset-0 items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 hidden">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                            </svg>
                        </div>
                    </a>
                    
                    {{-- Photo Type Badge --}}
                    @if($photo->photo_type)
                    @php
                        $typeColors = [
                            'before' => 'bg-amber-500',
                            'after' => 'bg-green-500',
                            'progress' => 'bg-blue-500',
                            'clinical' => 'bg-purple-500',
                        ];
                    @endphp
                    <div class="absolute top-2 left-2 z-10">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold text-white {{ $typeColors[$photo->photo_type] ?? 'bg-gray-500' }}">
                            {{ strtoupper($photo->photo_type) }}
                        </span>
                    </div>
                    @endif

                    {{-- Delete button (top right) --}}
                    <button @click.prevent="showDelete = true" 
                            class="absolute top-2 right-2 z-10 w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    
                    {{-- Hover Overlay --}}
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3 opacity-0 group-hover:opacity-100 transition-opacity">
                        <p class="text-white text-xs font-medium truncate">{{ $photo->patient?->name ?? 'Unknown' }}</p>
                        <p class="text-white/70 text-[10px] truncate">{{ $photo->body_region ?? 'No region' }}</p>
                        <p class="text-white/50 text-[10px]">{{ $photo->created_at?->format('d M Y') }}</p>
                    </div>

                    {{-- Delete Confirmation Modal --}}
                    <div x-show="showDelete" 
                         x-transition
                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                         @click.self="showDelete = false"
                         style="display: none;">
                        <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full" @click.stop>
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Delete Photo?</h3>
                                <p class="text-sm text-gray-500 mt-2">This will permanently delete the photo for <strong>{{ $photo->patient?->name ?? 'Unknown' }}</strong>.</p>
                            </div>
                            <div class="flex gap-3 mt-6">
                                <button @click="showDelete = false" 
                                        class="flex-1 px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                    Cancel
                                </button>
                                <form action="{{ route('patients.delete-photo', ['patient' => $photo->patient_id, 'photo' => $photo->id]) }}" 
                                      method="POST" class="flex-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="w-full px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        
        {{-- Pagination --}}
        @if(method_exists($photos, 'links'))
        <div class="px-5 py-4 border-t border-gray-200">
            {{ $photos->withQueryString()->links() }}
        </div>
        @endif
    </div>
    @else
    {{-- Empty State --}}
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <div class="w-16 h-16 mx-auto bg-purple-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900">No photos yet</h2>
        <p class="text-gray-500 mt-2 max-w-lg mx-auto">
            Clinical photos are uploaded from patient profiles during visits. Go to a patient's profile to upload before/after photos.
        </p>
        
        <div class="mt-8 grid grid-cols-3 gap-4 max-w-md mx-auto text-left">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm text-gray-600">Encrypted storage</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm text-gray-600">Before/After</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm text-gray-600">Body mapping</span>
            </div>
        </div>
        
        <div class="mt-6">
            <a href="{{ route('patients.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                View Patients
            </a>
        </div>
    </div>
    @endif

    {{-- Recent Activity by Patient --}}
    @if(isset($recentByPatient) && $recentByPatient->count() > 0)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Recent Uploads by Patient</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($recentByPatient as $item)
            @if($item->patient)
            <a href="{{ route('photo-vault.index', ['patient_id' => $item->patient_id]) }}" class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 transition-colors">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white font-bold">
                    {{ strtoupper(substr($item->patient->name ?? 'P', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">{{ $item->patient->name }}</p>
                    <p class="text-xs text-gray-500">{{ $item->photo_count }} photos</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400">Last upload</p>
                    <p class="text-sm text-gray-600">{{ \Carbon\Carbon::parse($item->latest)->diffForHumans() }}</p>
                </div>
            </a>
            @endif
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
