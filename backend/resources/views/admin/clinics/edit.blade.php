@extends('admin.layouts.app')

@section('title', 'Edit Clinic')
@section('subtitle', $clinic->name)

@section('content')
<div class="max-w-3xl">
    <form action="{{ route('admin.clinics.update', $clinic) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Clinic Information --}}
        <div class="bg-white rounded-xl p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Clinic Information</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Clinic Name *</label>
                    <input type="text" name="name" value="{{ old('name', $clinic->name) }}" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Specialty</label>
                    @php
                        $currentSpecialty = is_array($clinic->specialties) ? ($clinic->specialties[0] ?? '') : $clinic->specialties;
                    @endphp
                    <select name="specialty" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select Specialty</option>
                        @foreach(['general', 'dermatology', 'dental', 'ophthalmology', 'pediatrics', 'orthopedics', 'cardiology', 'gynecology', 'physiotherapy', 'ayurveda', 'homeopathy', 'multi_specialty'] as $spec)
                        <option value="{{ $spec }}" {{ $currentSpecialty === $spec ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $spec)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Plan *</label>
                    <select name="plan" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="trial" {{ old('plan', $clinic->plan) === 'trial' ? 'selected' : '' }}>Trial</option>
                        <option value="solo" {{ old('plan', $clinic->plan) === 'solo' ? 'selected' : '' }}>Solo (₹999/month)</option>
                        <option value="small" {{ old('plan', $clinic->plan) === 'small' ? 'selected' : '' }}>Small (₹2,499/month)</option>
                        <option value="group" {{ old('plan', $clinic->plan) === 'group' ? 'selected' : '' }}>Group (₹4,999/month)</option>
                        <option value="enterprise" {{ old('plan', $clinic->plan) === 'enterprise' ? 'selected' : '' }}>Enterprise (Custom)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email', $clinic->email) }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $clinic->phone) }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                    <input type="text" name="city" value="{{ old('city', $clinic->city) }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">State</label>
                    <input type="text" name="state" value="{{ old('state', $clinic->state) }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">GSTIN</label>
                    <input type="text" name="gstin" value="{{ old('gstin', $clinic->gstin) }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="27AADCS1234B1ZP">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Trial Ends At</label>
                    <input type="date" name="trial_ends_at" value="{{ old('trial_ends_at', $clinic->trial_ends_at?->format('Y-m-d')) }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $clinic->is_active) ? 'checked' : '' }}
                            class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Clinic is active</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Owner Information (Read-only) --}}
        @if($clinic->owner)
        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Owner Information</h3>
            <p class="text-sm text-gray-500 mb-4">To edit owner details, go to Users management.</p>
            
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                    <span class="text-indigo-600 font-semibold">{{ substr($clinic->owner->name, 0, 1) }}</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900">{{ $clinic->owner->name }}</p>
                    <p class="text-sm text-gray-500">{{ $clinic->owner->email }}</p>
                </div>
                <a href="{{ route('admin.users.edit', $clinic->owner) }}" class="ml-auto px-4 py-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                    Edit Owner →
                </a>
            </div>
        </div>
        @endif

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.clinics.show', $clinic) }}" class="px-6 py-2.5 text-gray-700 hover:text-gray-900">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
