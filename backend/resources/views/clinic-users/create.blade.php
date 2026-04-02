@extends('layouts.app')

@section('title', 'Add User')
@section('breadcrumb', 'Users & Staff / Add User')

@section('content')
<div class="p-6 max-w-2xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-6">
        <a href="{{ route('clinic.users.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            Back to Users
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Add New User</h1>
        <p class="text-sm text-gray-500 mt-1">Create a new team member account. They will be able to login immediately.</p>
    </div>

    {{-- Form --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <form action="{{ route('clinic.users.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                    placeholder="Dr. John Doe">
                @error('name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                    placeholder="john@clinic.com">
                @error('email')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Phone --}}
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-500 @enderror"
                    placeholder="+91 98765 43210">
                @error('phone')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Role --}}
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                <select name="role" id="role" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('role') border-red-500 @enderror">
                    <option value="">Select a role...</option>
                    <option value="doctor" {{ old('role') === 'doctor' ? 'selected' : '' }}>Doctor</option>
                    <option value="receptionist" {{ old('role') === 'receptionist' ? 'selected' : '' }}>Receptionist</option>
                    <option value="nurse" {{ old('role') === 'nurse' ? 'selected' : '' }}>Nurse</option>
                    <option value="pharmacist" {{ old('role') === 'pharmacist' ? 'selected' : '' }}>Pharmacist</option>
                    <option value="lab_technician" {{ old('role') === 'lab_technician' ? 'selected' : '' }}>Lab Technician</option>
                    <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Other Staff</option>
                </select>
                @error('role')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Each role has different access permissions</p>
            </div>

            {{-- Specialty --}}
            <div>
                <label for="specialty" class="block text-sm font-medium text-gray-700 mb-2">Specialty / Department</label>
                <input type="text" name="specialty" id="specialty" value="{{ old('specialty') }}"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('specialty') border-red-500 @enderror"
                    placeholder="Dermatology, General Medicine, etc.">
                @error('specialty')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Consultation Fee (₹)</label>
                        <input type="number" name="consultation_fee" min="0" step="50"
                               value="{{ old('consultation_fee', 0) }}"
                               placeholder="e.g. 500"
                               class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Used for auto-invoice generation on visit finalisation.</p>
                    </div>

            <hr class="border-gray-200">

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                <input type="password" name="password" id="password" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror"
                    placeholder="Minimum 6 characters">
                @error('password')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password <span class="text-red-500">*</span></label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Re-enter password">
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('clinic.users.index') }}" class="px-6 py-3 border border-gray-300 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Create User
                </button>
            </div>
        </form>
    </div>

    {{-- Info Box --}}
    <div class="mt-6 p-4 bg-blue-50 border border-blue-100 rounded-xl">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-900">Login Information</p>
                <p class="text-sm text-blue-700 mt-1">
                    Once created, the user can log in at <strong>{{ url('/login') }}</strong> using their email and password.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
