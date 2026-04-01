@extends('layouts.app')

@section('title', $patient->name)
@section('breadcrumb', 'Patient Details')

@section('content')
<div class="p-6 space-y-6">
    {{-- Patient Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-start gap-5">
            {{-- Avatar --}}
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-2xl font-bold text-white flex-shrink-0"
                 style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                {{ strtoupper(substr($patient->name, 0, 2)) }}
            </div>

            {{-- Info --}}
            <div class="flex-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900 font-display">{{ $patient->name }}</h1>
                    @if($patient->abha_number)
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                        🛡️ ABHA: {{ $patient->abha_number }}
                    </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-4 mt-3 text-sm">
                    <span class="flex items-center gap-1.5 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        {{ $patient->age ?? 'N/A' }} yrs · {{ $patient->gender == 'M' ? 'Male' : ($patient->gender == 'F' ? 'Female' : 'Other') }}
                    </span>
                    <span class="flex items-center gap-1.5 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $patient->phone }}
                    </span>
                    @if($patient->email)
                    <span class="flex items-center gap-1.5 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ $patient->email }}
                    </span>
                    @endif
                    @if($patient->blood_group)
                    <span class="px-2.5 py-1 bg-red-50 text-red-700 text-xs font-semibold rounded-lg">
                        🩸 {{ $patient->blood_group }}
                    </span>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">
                <a href="{{ route('patients.edit', $patient) }}" class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                    Edit
                </a>
                <form action="{{ route('emr.create', $patient) }}" method="POST" style="display:inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors">
                        Start Visit
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Visit History --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900">Visit History</h3>
                    <span class="text-sm text-gray-500">{{ $patient->visits->count() ?? 0 }} total visits</span>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($patient->visits()->latest()->take(10)->get() as $visit)
                    <a href="{{ route('emr.show', ['patient' => $patient->id, 'visit' => $visit->id]) }}" class="block px-5 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $visit->chief_complaint ?? 'General Consultation' }}</p>
                                <p class="text-sm text-gray-500 mt-0.5">{{ $visit->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $visit->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ ucfirst($visit->status) }}
                            </span>
                        </div>
                    </a>
                    @empty
                    <div class="px-5 py-12 text-center text-gray-500">
                        No visits recorded yet
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Invoices --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900">Invoices</h3>
                    <a href="{{ route('billing.create') }}?patient_id={{ $patient->id }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">New Invoice</a>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($patient->invoices()->latest()->take(5)->get() as $invoice)
                    <div class="px-5 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $invoice->invoice_number }}</p>
                            <p class="text-sm text-gray-500">{{ $invoice->created_at->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900">₹{{ number_format($invoice->total_amount) }}</p>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-8 text-center text-gray-500 text-sm">
                        No invoices yet
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Clinical Photos --}}
            <div class="bg-white rounded-xl border border-gray-200" x-data="{ showUploadModal: {{ $errors->any() ? 'true' : 'false' }} }">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900">Clinical Photos</h3>
                    <button @click="showUploadModal = true" class="inline-flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-700 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Upload Photo
                    </button>
                </div>
                
                {{-- Photos Grid --}}
                <div class="p-5">
                    @php
                        $photos = $patient->photos()->latest()->take(8)->get();
                    @endphp
                    
                    @if($photos->count() > 0)
                    <div class="grid grid-cols-4 gap-3">
                        @foreach($photos as $photo)
                        @php
                            $photoUrl = route('patients.view-photo', ['patient' => $patient->id, 'photo' => $photo->id]);
                        @endphp
                        <div class="group relative aspect-square bg-gray-100 rounded-lg overflow-hidden" x-data="{ showDelete: false }">
                            {{-- Actual Photo (using secure route) --}}
                            <a href="{{ $photoUrl }}" target="_blank" class="block w-full h-full">
                                <img src="{{ $photoUrl }}" 
                                     alt="{{ $photo->body_region ?? 'Clinical photo' }}"
                                     class="absolute inset-0 w-full h-full object-cover"
                                     loading="lazy"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                {{-- Fallback placeholder --}}
                                <div class="absolute inset-0 items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 hidden">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                                    </svg>
                                </div>
                            </a>
                            
                            {{-- Type badge --}}
                            @if($photo->photo_type)
                            @php
                                $typeColors = [
                                    'before' => 'bg-amber-500',
                                    'after' => 'bg-green-500',
                                    'progress' => 'bg-blue-500',
                                    'clinical' => 'bg-purple-500',
                                ];
                            @endphp
                            <div class="absolute top-1 left-1 z-10">
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold text-white {{ $typeColors[$photo->photo_type] ?? 'bg-gray-500' }}">
                                    {{ strtoupper($photo->photo_type) }}
                                </span>
                            </div>
                            @endif
                            
                            {{-- Delete button (top right) --}}
                            <button @click.prevent="showDelete = true" 
                                    class="absolute top-1 right-1 z-10 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            
                            {{-- Hover overlay with body region --}}
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="text-white text-xs">{{ $photo->body_region ?? 'View' }}</span>
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
                                        <p class="text-sm text-gray-500 mt-2">This action cannot be undone. The photo will be permanently deleted.</p>
                                    </div>
                                    <div class="flex gap-3 mt-6">
                                        <button @click="showDelete = false" 
                                                class="flex-1 px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                            Cancel
                                        </button>
                                        <form action="{{ route('patients.delete-photo', ['patient' => $patient->id, 'photo' => $photo->id]) }}" 
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
                    
                    @if($patient->photos()->count() > 8)
                    <div class="mt-4 text-center">
                        <a href="{{ route('photo-vault.index') }}?patient_id={{ $patient->id }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                            View all {{ $patient->photos()->count() }} photos →
                        </a>
                    </div>
                    @endif
                    @else
                    <div class="py-8 text-center">
                        <div class="w-12 h-12 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500">No clinical photos yet</p>
                        <button @click="showUploadModal = true" class="mt-2 text-sm text-blue-600 hover:text-blue-700 font-medium">
                            Upload first photo
                        </button>
                    </div>
                    @endif
                </div>

                {{-- Upload Modal --}}
                <div x-show="showUploadModal" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                     @click.self="showUploadModal = false"
                     style="display: none;">
                    <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full max-h-[90vh] overflow-y-auto"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100">
                        
                        {{-- Modal Header --}}
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900">Upload Clinical Photo</h3>
                            <button @click="showUploadModal = false" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Modal Body --}}
                        <form action="{{ route('patients.upload-photo', $patient) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
                            @csrf
                            
                            {{-- Validation Errors --}}
                            @if($errors->any())
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-center gap-2 text-red-700 mb-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="font-semibold text-sm">Please fix the following errors:</span>
                                </div>
                                <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                                    @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            
                            {{-- Photo Upload Area --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Photo</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-400 transition-colors">
                                    <input type="file" name="photo" id="photo-input" accept="image/*" required
                                           class="hidden"
                                           onchange="previewImage(this)">
                                    <label for="photo-input" class="cursor-pointer">
                                        <div id="preview-container" class="hidden mb-4">
                                            <img id="preview-image" class="max-h-48 mx-auto rounded-lg" src="">
                                        </div>
                                        <div id="upload-placeholder">
                                            <svg class="w-10 h-10 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <p class="text-sm text-gray-600">Click to select photo</p>
                                            <p class="text-xs text-gray-400 mt-1">JPG, PNG up to 10MB</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Photo Type --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Photo Type</label>
                                <div class="grid grid-cols-4 gap-2">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="photo_type" value="before" class="sr-only peer" required>
                                        <div class="px-3 py-2 text-center text-sm font-medium border border-gray-300 rounded-lg peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700 hover:bg-gray-50 transition-colors">
                                            Before
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="photo_type" value="after" class="sr-only peer">
                                        <div class="px-3 py-2 text-center text-sm font-medium border border-gray-300 rounded-lg peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 hover:bg-gray-50 transition-colors">
                                            After
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="photo_type" value="progress" class="sr-only peer">
                                        <div class="px-3 py-2 text-center text-sm font-medium border border-gray-300 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 hover:bg-gray-50 transition-colors">
                                            Progress
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="photo_type" value="clinical" class="sr-only peer">
                                        <div class="px-3 py-2 text-center text-sm font-medium border border-gray-300 rounded-lg peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:text-purple-700 hover:bg-gray-50 transition-colors">
                                            Clinical
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Body Region --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Body Region</label>
                                <select name="body_region" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select region...</option>
                                    <option value="face">Face</option>
                                    <option value="forehead">Forehead</option>
                                    <option value="cheeks">Cheeks</option>
                                    <option value="nose">Nose</option>
                                    <option value="chin">Chin</option>
                                    <option value="neck">Neck</option>
                                    <option value="scalp">Scalp</option>
                                    <option value="chest">Chest</option>
                                    <option value="back">Back</option>
                                    <option value="abdomen">Abdomen</option>
                                    <option value="arms">Arms</option>
                                    <option value="hands">Hands</option>
                                    <option value="legs">Legs</option>
                                    <option value="feet">Feet</option>
                                    <option value="nails">Nails</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            {{-- Condition Tag --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Condition/Procedure (Optional)</label>
                                <input type="text" name="condition_tag" placeholder="e.g., Acne, Chemical Peel, Laser"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <x-photo-consent-signature :patient-id="$patient->id" title="Patient signature (optional)" />

                            {{-- Consent Checkbox --}}
                            <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-xl">
                                <input type="checkbox" name="consent_obtained" id="consent_obtained" value="1" required
                                       class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label for="consent_obtained" class="text-sm text-gray-600">
                                    Patient has given consent for clinical photography. Photos will be stored securely and used only for medical purposes.
                                </label>
                            </div>

                            {{-- Submit --}}
                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="showUploadModal = false" 
                                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors">
                                    Upload Photo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Medical Info --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="font-bold text-gray-900">Medical Information</h3>
                </div>
                <div class="p-5 space-y-4 text-sm">
                    <div>
                        <p class="text-gray-500 mb-1">Allergies</p>
                        <p class="text-gray-900">{{ $patient->allergies ?: 'None recorded' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 mb-1">Chronic Conditions</p>
                        <p class="text-gray-900">{{ $patient->chronic_conditions ?: 'None recorded' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 mb-1">Current Medications</p>
                        <p class="text-gray-900">{{ $patient->current_medications ?: 'None recorded' }}</p>
                    </div>
                </div>
            </div>

            {{-- Emergency Contact --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="font-bold text-gray-900">Emergency Contact</h3>
                </div>
                <div class="p-5 text-sm">
                    @if($patient->emergency_contact_name)
                    <p class="font-semibold text-gray-900">{{ $patient->emergency_contact_name }}</p>
                    <p class="text-gray-500">{{ $patient->emergency_contact_relation }}</p>
                    <p class="text-gray-600 mt-1">{{ $patient->emergency_contact_phone }}</p>
                    @else
                    <p class="text-gray-500">Not provided</p>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="font-bold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <button class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        </svg>
                        Send WhatsApp
                    </button>
                    <a href="{{ route('appointments.create') }}?patient_id={{ $patient->id }}" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Book Appointment
                    </a>
                    <a href="{{ route('billing.create') }}?patient_id={{ $patient->id }}" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                        Create Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewImage(input) {
    const preview = document.getElementById('preview-image');
    const previewContainer = document.getElementById('preview-container');
    const placeholder = document.getElementById('upload-placeholder');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
