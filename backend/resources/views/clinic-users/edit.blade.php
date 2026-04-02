@extends('layouts.app')

@section('title', 'Edit User')
@section('breadcrumb', 'Users & Staff / Edit User')

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
        <h1 class="text-2xl font-bold text-gray-900">Edit User</h1>
        <p class="text-sm text-gray-500 mt-1">Update user information and access settings.</p>
    </div>

    {{-- User Info Card --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold text-white flex-shrink-0
                @if($user->role === 'owner') bg-gradient-to-br from-amber-500 to-orange-600
                @elseif($user->role === 'doctor') bg-gradient-to-br from-blue-500 to-indigo-600
                @elseif($user->role === 'receptionist') bg-gradient-to-br from-purple-500 to-pink-600
                @elseif($user->role === 'nurse') bg-gradient-to-br from-pink-500 to-rose-600
                @else bg-gradient-to-br from-gray-500 to-gray-600 @endif
            ">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-gray-900">{{ $user->name }}</h2>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                        @if($user->role === 'owner') bg-amber-100 text-amber-800
                        @elseif($user->role === 'doctor') bg-blue-100 text-blue-800
                        @elseif($user->role === 'receptionist') bg-purple-100 text-purple-800
                        @elseif($user->role === 'nurse') bg-pink-100 text-pink-800
                        @else bg-gray-100 text-gray-800 @endif
                    ">{{ ucfirst($user->role) }}</span>
                    @if($user->is_active)
                    <span class="px-2 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700 rounded-full">Active</span>
                    @else
                    <span class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded-full">Inactive</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <form action="{{ route('clinic.users.update', $user) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                    placeholder="Dr. John Doe">
                @error('name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                    placeholder="john@clinic.com">
                @error('email')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Phone --}}
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
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
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('role') border-red-500 @enderror"
                    @if($user->role === 'owner') disabled @endif>
                    <option value="owner" {{ old('role', $user->role) === 'owner' ? 'selected' : '' }}>Owner</option>
                    <option value="doctor" {{ old('role', $user->role) === 'doctor' ? 'selected' : '' }}>Doctor</option>
                    <option value="receptionist" {{ old('role', $user->role) === 'receptionist' ? 'selected' : '' }}>Receptionist</option>
                    <option value="nurse" {{ old('role', $user->role) === 'nurse' ? 'selected' : '' }}>Nurse</option>
                    <option value="pharmacist" {{ old('role', $user->role) === 'pharmacist' ? 'selected' : '' }}>Pharmacist</option>
                    <option value="lab_technician" {{ old('role', $user->role) === 'lab_technician' ? 'selected' : '' }}>Lab Technician</option>
                    <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Other Staff</option>
                </select>
                @if($user->role === 'owner')
                    <input type="hidden" name="role" value="owner">
                    <p class="mt-1 text-xs text-amber-600">Owner role cannot be changed</p>
                @endif
                @error('role')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Specialty --}}
            <div>
                <label for="specialty" class="block text-sm font-medium text-gray-700 mb-2">Specialty / Department</label>
                <input type="text" name="specialty" id="specialty" value="{{ old('specialty', $user->specialty) }}"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('specialty') border-red-500 @enderror"
                    placeholder="Dermatology, General Medicine, etc.">
                @error('specialty')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <hr class="border-gray-200">

            <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl">
                <h4 class="text-sm font-medium text-amber-900 mb-2">Change Password (Optional)</h4>
                <p class="text-xs text-amber-700 mb-4">Leave blank to keep the current password.</p>

                {{-- Password --}}
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" name="password" id="password"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror"
                        placeholder="Minimum 6 characters">
                    @error('password')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Re-enter new password">
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('clinic.users.index') }}" class="px-6 py-3 border border-gray-300 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Update User
                </button>
            </div>
        </form>
    </div>

    {{-- Danger Zone --}}
    @if($user->id !== auth()->id() && $user->role !== 'owner')
    <div class="mt-6 bg-white rounded-2xl border border-red-200 overflow-hidden">
        <div class="px-6 py-4 bg-red-50 border-b border-red-200">
            <h3 class="font-semibold text-red-900">Danger Zone</h3>
        </div>
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-medium text-gray-900">Delete this user</h4>
                    <p class="text-xs text-gray-500 mt-1">Once deleted, this user will no longer be able to access the system.</p>
                </div>
                <form action="{{ route('clinic.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors">
                        Delete User
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
