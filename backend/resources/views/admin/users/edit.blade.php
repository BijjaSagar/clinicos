@extends('admin.layouts.app')

@section('title', 'Edit User')
@section('subtitle', $user->name)

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">User Information</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                    <input type="password" name="password"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Leave blank to keep current password">
                    <p class="text-xs text-gray-500 mt-1">Minimum 8 characters. Leave blank to keep current password.</p>
                    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Role *</label>
                        <select name="role" id="role-select" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                            <option value="owner" {{ old('role', $user->role) === 'owner' ? 'selected' : '' }}>Clinic Owner</option>
                            <option value="doctor" {{ old('role', $user->role) === 'doctor' ? 'selected' : '' }}>Doctor</option>
                            <option value="receptionist" {{ old('role', $user->role) === 'receptionist' ? 'selected' : '' }}>Receptionist</option>
                            <option value="nurse" {{ old('role', $user->role) === 'nurse' ? 'selected' : '' }}>Nurse</option>
                            <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                    </div>

                    <div id="clinic-field">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Clinic</label>
                        <select name="clinic_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">No Clinic</option>
                            @foreach($clinics as $clinic)
                            <option value="{{ $clinic->id }}" {{ old('clinic_id', $user->clinic_id) == $clinic->id ? 'selected' : '' }}>{{ $clinic->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="specialty-field">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Specialty</label>
                    <select name="specialty" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select Specialty</option>
                        @foreach(['general', 'dermatology', 'dental', 'ophthalmology', 'pediatrics', 'orthopedics', 'cardiology', 'gynecology', 'physiotherapy'] as $spec)
                        <option value="{{ $spec }}" {{ old('specialty', $user->specialty) === $spec ? 'selected' : '' }}>{{ ucfirst($spec) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                            class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">User is active</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- User Info --}}
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">Created</p>
                    <p class="font-medium text-gray-900">{{ $user->created_at->format('d M Y, h:i A') }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Last Updated</p>
                    <p class="font-medium text-gray-900">{{ $user->updated_at->format('d M Y, h:i A') }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Last Login</p>
                    <p class="font-medium text-gray-900">{{ $user->last_login_at ? $user->last_login_at->format('d M Y, h:i A') : 'Never' }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.users.index') }}" class="px-6 py-2.5 text-gray-700 hover:text-gray-900">
                Cancel
            </a>
            <div class="flex items-center gap-3">
                @if($user->id !== auth()->id())
                <button type="button" onclick="document.getElementById('delete-form').submit()" 
                    class="px-4 py-2.5 text-red-600 hover:text-red-700 font-medium">
                    Delete User
                </button>
                @endif
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors">
                    Save Changes
                </button>
            </div>
        </div>
    </form>

    @if($user->id !== auth()->id())
    <form id="delete-form" action="{{ route('admin.users.destroy', $user) }}" method="POST" class="hidden" onsubmit="return confirm('Are you sure you want to delete this user?')">
        @csrf
        @method('DELETE')
    </form>
    @endif
</div>

@push('scripts')
<script>
    const roleSelect = document.getElementById('role-select');
    const clinicField = document.getElementById('clinic-field');
    const specialtyField = document.getElementById('specialty-field');
    
    function updateFields() {
        const role = roleSelect.value;
        clinicField.style.display = role === 'super_admin' ? 'none' : 'block';
        specialtyField.style.display = role === 'doctor' ? 'block' : 'none';
    }
    
    roleSelect.addEventListener('change', updateFields);
    updateFields();
</script>
@endpush
@endsection
