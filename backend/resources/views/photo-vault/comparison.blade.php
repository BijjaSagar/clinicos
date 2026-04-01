@extends('layouts.app')

@section('title', 'Photo Comparison - ' . $patient->name)
@section('breadcrumb', 'Photo Comparison')

@section('content')
<div class="p-6 space-y-6" x-data="photoComparison()">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('photo-vault.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900">Before/After Comparison</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ $patient->name }} · Patient ID: {{ $patient->patient_id }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('patients.show', $patient) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                View Profile
            </a>
        </div>
    </div>

    {{-- View Mode Tabs --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button @click="viewMode = 'sideBySide'" :class="viewMode === 'sideBySide' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors">
                    Side by Side
                </button>
                <button @click="viewMode = 'slider'" :class="viewMode === 'slider' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors">
                    Slider Comparison
                </button>
                <button @click="viewMode = 'timeline'" :class="viewMode === 'timeline' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors">
                    Timeline View
                </button>
                <button @click="viewMode = 'bodyMap'" :class="viewMode === 'bodyMap' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors">
                    Body Map
                </button>
            </nav>
        </div>

        {{-- Side by Side View --}}
        <div x-show="viewMode === 'sideBySide'" class="p-6">
            <div class="grid grid-cols-2 gap-6">
                {{-- Before --}}
                <div>
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        Before
                    </h3>
                    @if($beforePhotos->count() > 0)
                    <div class="space-y-4">
                        @foreach($beforePhotos as $photo)
                        <div class="bg-gray-100 rounded-xl overflow-hidden cursor-pointer hover:ring-2 hover:ring-amber-500" @click="selectPhoto('before', {{ $photo->id }})">
                            <img src="{{ route('patients.view-photo', ['patient' => $patient->id, 'photo' => $photo->id]) }}" 
                                 alt="Before photo" 
                                 class="w-full aspect-[4/3] object-cover">
                            <div class="p-3 bg-white">
                                <p class="text-sm font-medium text-gray-900">{{ $photo->body_region ?? 'Unspecified region' }}</p>
                                <p class="text-xs text-gray-500">{{ $photo->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="bg-gray-100 rounded-xl p-8 text-center">
                        <p class="text-gray-500">No "Before" photos uploaded</p>
                    </div>
                    @endif
                </div>

                {{-- After --}}
                <div>
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        After
                    </h3>
                    @if($afterPhotos->count() > 0)
                    <div class="space-y-4">
                        @foreach($afterPhotos as $photo)
                        <div class="bg-gray-100 rounded-xl overflow-hidden cursor-pointer hover:ring-2 hover:ring-green-500" @click="selectPhoto('after', {{ $photo->id }})">
                            <img src="{{ route('patients.view-photo', ['patient' => $patient->id, 'photo' => $photo->id]) }}" 
                                 alt="After photo" 
                                 class="w-full aspect-[4/3] object-cover">
                            <div class="p-3 bg-white">
                                <p class="text-sm font-medium text-gray-900">{{ $photo->body_region ?? 'Unspecified region' }}</p>
                                <p class="text-xs text-gray-500">{{ $photo->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="bg-gray-100 rounded-xl p-8 text-center">
                        <p class="text-gray-500">No "After" photos uploaded</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Slider Comparison View --}}
        <div x-show="viewMode === 'slider'" class="p-6">
            <div class="max-w-3xl mx-auto">
                <div class="mb-4 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Before Photo</label>
                        <select x-model="sliderBeforeId" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Select before photo</option>
                            @foreach($beforePhotos as $photo)
                            <option value="{{ $photo->id }}">{{ $photo->body_region ?? 'No region' }} - {{ $photo->created_at->format('d M Y') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">After Photo</label>
                        <select x-model="sliderAfterId" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Select after photo</option>
                            @foreach($afterPhotos as $photo)
                            <option value="{{ $photo->id }}">{{ $photo->body_region ?? 'No region' }} - {{ $photo->created_at->format('d M Y') }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="relative aspect-[4/3] bg-gray-900 rounded-xl overflow-hidden" x-show="sliderBeforeId && sliderAfterId">
                    {{-- After Image (Background) --}}
                    <img :src="'/patients/' + {{ $patient->id }} + '/photos/' + sliderAfterId" 
                         alt="After" 
                         class="absolute inset-0 w-full h-full object-cover"
                         x-show="sliderAfterId">
                    
                    {{-- Before Image (Clipped) --}}
                    <div class="absolute inset-0 overflow-hidden" :style="'clip-path: inset(0 ' + (100 - sliderPosition) + '% 0 0)'">
                        <img :src="'/patients/' + {{ $patient->id }} + '/photos/' + sliderBeforeId" 
                             alt="Before" 
                             class="absolute inset-0 w-full h-full object-cover"
                             x-show="sliderBeforeId">
                    </div>
                    
                    {{-- Slider Handle --}}
                    <div class="absolute inset-y-0 w-1 bg-white shadow-lg cursor-ew-resize" 
                         :style="'left: ' + sliderPosition + '%'"
                         @mousedown="isDragging = true"
                         @touchstart="isDragging = true">
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                            </svg>
                        </div>
                    </div>
                    
                    {{-- Labels --}}
                    <div class="absolute top-4 left-4 px-2 py-1 bg-amber-500 text-white text-xs font-bold rounded">BEFORE</div>
                    <div class="absolute top-4 right-4 px-2 py-1 bg-green-500 text-white text-xs font-bold rounded">AFTER</div>
                </div>

                <div class="mt-4" x-show="sliderBeforeId && sliderAfterId">
                    <input type="range" min="0" max="100" x-model="sliderPosition" class="w-full">
                </div>

                <div x-show="!sliderBeforeId || !sliderAfterId" class="aspect-[4/3] bg-gray-100 rounded-xl flex items-center justify-center">
                    <p class="text-gray-500">Select both before and after photos to compare</p>
                </div>
            </div>
        </div>

        {{-- Timeline View --}}
        <div x-show="viewMode === 'timeline'" class="p-6">
            <div class="relative">
                <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                
                <div class="space-y-8">
                    @foreach($photos->sortBy('created_at') as $photo)
                    <div class="relative pl-16">
                        <div class="absolute left-4 w-5 h-5 rounded-full border-4 border-white shadow
                            @if($photo->photo_type === 'before') bg-amber-500
                            @elseif($photo->photo_type === 'after') bg-green-500
                            @elseif($photo->photo_type === 'progress') bg-blue-500
                            @else bg-gray-400
                            @endif
                        "></div>
                        
                        <div class="bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors">
                            <div class="flex gap-4">
                                <img src="{{ route('patients.view-photo', ['patient' => $patient->id, 'photo' => $photo->id]) }}" 
                                     alt="{{ $photo->body_region ?? 'Photo' }}" 
                                     class="w-24 h-24 object-cover rounded-lg flex-shrink-0">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-semibold text-gray-900">{{ $photo->created_at->format('d M Y') }}</span>
                                        <span class="text-xs text-gray-400">{{ $photo->created_at->format('h:i A') }}</span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ $photo->body_region ?? 'No region specified' }}</p>
                                    @if($photo->photo_type)
                                    <span class="inline-flex mt-2 px-2 py-0.5 text-xs font-medium rounded-full
                                        @if($photo->photo_type === 'before') bg-amber-100 text-amber-700
                                        @elseif($photo->photo_type === 'after') bg-green-100 text-green-700
                                        @elseif($photo->photo_type === 'progress') bg-blue-100 text-blue-700
                                        @else bg-gray-100 text-gray-700
                                        @endif
                                    ">{{ ucfirst($photo->photo_type) }}</span>
                                    @endif
                                    @if($photo->description)
                                    <p class="text-xs text-gray-500 mt-2">{{ $photo->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Body Map View --}}
        <div x-show="viewMode === 'bodyMap'" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Body Diagram --}}
                <div class="lg:col-span-1">
                    <div class="bg-gray-100 rounded-xl p-4">
                        <h4 class="text-sm font-semibold text-gray-900 mb-4">Photo Locations</h4>
                        
                        <div class="relative aspect-[3/4] bg-white rounded-lg overflow-hidden">
                            {{-- SVG Body Outline --}}
                            <svg viewBox="0 0 200 300" class="w-full h-full">
                                {{-- Simple body outline --}}
                                <g fill="none" stroke="#d1d5db" stroke-width="2">
                                    {{-- Head --}}
                                    <ellipse cx="100" cy="30" rx="20" ry="25" class="body-part" data-region="face" :class="{'fill-blue-200 stroke-blue-500': selectedRegion === 'face'}" @click="selectedRegion = 'face'"/>
                                    {{-- Neck --}}
                                    <rect x="92" y="52" width="16" height="15" class="body-part" data-region="neck" :class="{'fill-blue-200 stroke-blue-500': selectedRegion === 'neck'}" @click="selectedRegion = 'neck'"/>
                                    {{-- Torso --}}
                                    <rect x="65" y="65" width="70" height="80" rx="10" class="body-part" data-region="torso" :class="{'fill-blue-200 stroke-blue-500': selectedRegion === 'torso'}" @click="selectedRegion = 'torso'"/>
                                    {{-- Left Arm --}}
                                    <rect x="30" y="70" width="30" height="70" rx="5" class="body-part" data-region="upper_limbs" :class="{'fill-blue-200 stroke-blue-500': selectedRegion === 'upper_limbs'}" @click="selectedRegion = 'upper_limbs'"/>
                                    {{-- Right Arm --}}
                                    <rect x="140" y="70" width="30" height="70" rx="5" class="body-part" data-region="upper_limbs" :class="{'fill-blue-200 stroke-blue-500': selectedRegion === 'upper_limbs'}" @click="selectedRegion = 'upper_limbs'"/>
                                    {{-- Left Leg --}}
                                    <rect x="65" y="150" width="30" height="100" rx="5" class="body-part" data-region="lower_limbs" :class="{'fill-blue-200 stroke-blue-500': selectedRegion === 'lower_limbs'}" @click="selectedRegion = 'lower_limbs'"/>
                                    {{-- Right Leg --}}
                                    <rect x="105" y="150" width="30" height="100" rx="5" class="body-part" data-region="lower_limbs" :class="{'fill-blue-200 stroke-blue-500': selectedRegion === 'lower_limbs'}" @click="selectedRegion = 'lower_limbs'"/>
                                </g>
                                
                                {{-- Photo count indicators --}}
                                @foreach($groupedByRegion as $region => $regionPhotos)
                                @php
                                    $positions = [
                                        'face' => ['cx' => 120, 'cy' => 30],
                                        'Full Face' => ['cx' => 120, 'cy' => 30],
                                        'neck' => ['cx' => 130, 'cy' => 60],
                                        'Chest' => ['cx' => 100, 'cy' => 90],
                                        'Upper Back' => ['cx' => 100, 'cy' => 90],
                                        'Abdomen' => ['cx' => 100, 'cy' => 120],
                                        'Hand' => ['cx' => 30, 'cy' => 130],
                                        'Fingers' => ['cx' => 30, 'cy' => 140],
                                        'Foot' => ['cx' => 80, 'cy' => 250],
                                    ];
                                    $pos = $positions[$region] ?? ['cx' => 100, 'cy' => 150];
                                @endphp
                                <g>
                                    <circle cx="{{ $pos['cx'] }}" cy="{{ $pos['cy'] }}" r="8" fill="#3b82f6"/>
                                    <text x="{{ $pos['cx'] }}" y="{{ $pos['cy'] + 3 }}" fill="white" font-size="8" text-anchor="middle">{{ $regionPhotos->count() }}</text>
                                </g>
                                @endforeach
                            </svg>
                        </div>
                        
                        <p class="text-xs text-gray-500 text-center mt-3">Click a body part to filter photos</p>
                    </div>
                </div>

                {{-- Photos by Region --}}
                <div class="lg:col-span-2">
                    <h4 class="text-sm font-semibold text-gray-900 mb-4">Photos by Body Region</h4>
                    
                    @forelse($groupedByRegion as $region => $regionPhotos)
                    <div class="mb-6" x-show="!selectedRegion || selectedRegion === '{{ strtolower(str_replace(' ', '_', $region)) }}'">
                        <h5 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            {{ $region ?? 'Unspecified' }}
                            <span class="text-xs text-gray-400">({{ $regionPhotos->count() }} photos)</span>
                        </h5>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach($regionPhotos as $photo)
                            <div class="bg-gray-100 rounded-lg overflow-hidden group cursor-pointer">
                                <img src="{{ route('patients.view-photo', ['patient' => $patient->id, 'photo' => $photo->id]) }}" 
                                     alt="{{ $region }}" 
                                     class="w-full aspect-square object-cover group-hover:scale-105 transition-transform">
                                <div class="p-2 bg-white">
                                    <p class="text-xs text-gray-500">{{ $photo->created_at->format('d M Y') }}</p>
                                    @if($photo->photo_type)
                                    <span class="inline-flex mt-1 px-1.5 py-0.5 text-[10px] font-medium rounded
                                        @if($photo->photo_type === 'before') bg-amber-100 text-amber-700
                                        @elseif($photo->photo_type === 'after') bg-green-100 text-green-700
                                        @else bg-gray-100 text-gray-700
                                        @endif
                                    ">{{ ucfirst($photo->photo_type) }}</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @empty
                    <div class="bg-gray-100 rounded-xl p-8 text-center">
                        <p class="text-gray-500">No photos with body region tags</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Consent signature (saved via API) --}}
    <x-photo-consent-signature :patient-id="$patient->id" title="Patient consent signature (recommended)" />

    {{-- Upload New Photo --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6" x-data="photoVaultUploadForm(@js(route('photo-vault.upload')))">
        <h3 class="font-semibold text-gray-900 mb-4">Upload New Photo</h3>
        <p class="text-xs text-gray-500 mb-4">Use the signature pad above to record consent, then confirm below and upload.</p>

        <form enctype="multipart/form-data" class="space-y-4" @submit.prevent="submitUpload($event)">
            @csrf
            <input type="hidden" name="patient_id" value="{{ $patient->id }}">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Photo Type</label>
                    <select name="photo_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="before">Before</option>
                        <option value="after">After</option>
                        <option value="progress">Progress</option>
                        <option value="clinical">Clinical</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Body Region</label>
                    <select name="body_region" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select region</option>
                        <option value="Face">Face</option>
                        <option value="Scalp">Scalp</option>
                        <option value="Neck">Neck</option>
                        <option value="Chest">Chest</option>
                        <option value="Back">Back</option>
                        <option value="Abdomen">Abdomen</option>
                        <option value="Upper Arm">Upper Arm</option>
                        <option value="Forearm">Forearm</option>
                        <option value="Hand">Hand</option>
                        <option value="Thigh">Thigh</option>
                        <option value="Leg">Leg</option>
                        <option value="Foot">Foot</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                    <input type="file" name="photo" accept="image/*" required 
                           @change="preview = URL.createObjectURL($event.target.files[0])"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Add notes about this photo..."></textarea>
            </div>
            
            <div class="flex items-start gap-3 p-4 bg-amber-50 rounded-xl border border-amber-100">
                <input type="checkbox" name="consent_confirmed" id="pv_consent_confirmed" value="1" required
                       class="mt-1 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="pv_consent_confirmed" class="text-sm text-gray-700">
                    I confirm that valid consent was obtained for this clinical photograph (signature above when possible).
                </label>
            </div>

            <div class="flex items-center gap-4">
                <img :src="preview" x-show="preview" class="w-20 h-20 object-cover rounded-lg">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                        :disabled="uploading">
                    <span x-show="!uploading">Upload Photo</span>
                    <span x-show="uploading">Uploading…</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
console.log('Photo comparison page loaded');

function photoVaultUploadForm(uploadUrl) {
    return {
        uploadUrl,
        uploading: false,
        preview: null,
        async submitUpload(event) {
            const form = event.target;
            const fd = new FormData(form);
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            console.log('[photoVaultUploadForm] submit', { uploadUrl: this.uploadUrl, keys: [...fd.keys()] });
            this.uploading = true;
            try {
                const res = await fetch(this.uploadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: fd,
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                console.log('[photoVaultUploadForm] response', { status: res.status, data });
                if (!res.ok) {
                    throw new Error(data.error || data.message || ('Upload failed: ' + res.status));
                }
                window.location.reload();
            } catch (e) {
                console.error('[photoVaultUploadForm]', e);
                alert(e.message || 'Upload failed');
            } finally {
                this.uploading = false;
            }
        },
    };
}

function photoComparison() {
    return {
        viewMode: 'sideBySide',
        selectedRegion: null,
        sliderPosition: 50,
        sliderBeforeId: null,
        sliderAfterId: null,
        isDragging: false,

        init() {
            console.log('Photo comparison initialized');
            
            document.addEventListener('mousemove', (e) => {
                if (this.isDragging) {
                    const container = document.querySelector('.aspect-\\[4\\/3\\]');
                    if (container) {
                        const rect = container.getBoundingClientRect();
                        const x = e.clientX - rect.left;
                        this.sliderPosition = Math.max(0, Math.min(100, (x / rect.width) * 100));
                    }
                }
            });

            document.addEventListener('mouseup', () => {
                this.isDragging = false;
            });
        },

        selectPhoto(type, photoId) {
            console.log('Selected photo:', type, photoId);
            if (type === 'before') {
                this.sliderBeforeId = photoId;
            } else {
                this.sliderAfterId = photoId;
            }
            this.viewMode = 'slider';
        }
    };
}
</script>
@endpush
