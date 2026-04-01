@extends('layouts.app')

@section('title', 'Clinic Locations')

@section('content')
<style>
    .location-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 24px;
        transition: all 0.2s;
    }
    .location-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .location-card.primary {
        border-color: #3b82f6;
        background: linear-gradient(135deg, #eff6ff, #fff);
    }
    .stat-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        background: #f3f4f6;
        border-radius: 20px;
        font-size: 13px;
        color: #6b7280;
    }
    .form-input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
    }
    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    .modal-content {
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
    }
</style>

<div x-data="locationsManager()" class="p-6 space-y-6">
    @if(isset($locationsSchemaReady) && !$locationsSchemaReady)
    <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
        <code class="bg-amber-100 px-1 rounded">clinic_locations</code> table is missing. Run <code class="bg-amber-100 px-1 rounded">php artisan migrate</code>.
    </div>
    @endif
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Clinic Locations</h1>
            <p class="text-gray-600 mt-1">Manage multiple clinic locations and their settings</p>
        </div>
        <button @click="showAddModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Location
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-5 border border-gray-200">
            <div class="text-3xl font-bold text-blue-600">{{ count($locations) }}</div>
            <div class="text-gray-600">Total Locations</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200">
            <div class="text-3xl font-bold text-green-600">{{ collect($locationStats)->sum(fn($s) => $s['rooms']) }}</div>
            <div class="text-gray-600">Total Rooms</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200">
            <div class="text-3xl font-bold text-purple-600">{{ collect($locationStats)->sum(fn($s) => $s['doctors']) }}</div>
            <div class="text-gray-600">Assigned Doctors</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200">
            <div class="text-3xl font-bold text-orange-600">{{ collect($locationStats)->sum(fn($s) => $s['appointments_today']) }}</div>
            <div class="text-gray-600">Appointments Today</div>
        </div>
    </div>

    {{-- Locations Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @forelse($locations as $location)
        <div class="location-card {{ $location->is_primary ? 'primary' : '' }}">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $location->name }}</h3>
                        @if($location->is_primary)
                        <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-medium">Primary</span>
                        @endif
                        @if(!$location->is_active)
                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs font-medium">Inactive</span>
                        @endif
                    </div>
                    <p class="text-gray-600 text-sm mt-1">{{ $location->address }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="editLocation({{ json_encode($location) }})" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </button>
                    @if(!$location->is_primary)
                    <button @click="deleteLocation({{ $location->id }})" class="p-2 hover:bg-red-50 rounded-lg text-red-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-3 mb-4">
                <span class="stat-badge">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                    </svg>
                    {{ $locationStats[$location->id]['rooms'] ?? 0 }} Rooms
                </span>
                <span class="stat-badge">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    {{ $locationStats[$location->id]['doctors'] ?? 0 }} Doctors
                </span>
                <span class="stat-badge">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ $locationStats[$location->id]['appointments_today'] ?? 0 }} Today
                </span>
            </div>

            @if($location->phone || $location->email)
            <div class="pt-4 border-t border-gray-100 text-sm text-gray-600 space-y-1">
                @if($location->phone)
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    {{ $location->phone }}
                </div>
                @endif
                @if($location->email)
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    {{ $location->email }}
                </div>
                @endif
            </div>
            @endif
        </div>
        @empty
        <div class="col-span-2 text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
            <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Locations Yet</h3>
            <p class="text-gray-600 mb-4">Add your first clinic location to get started</p>
            <button @click="showAddModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium">
                Add Location
            </button>
        </div>
        @endforelse
    </div>

    {{-- Add/Edit Location Modal --}}
    <div x-show="showAddModal || showEditModal" class="modal-overlay" x-transition>
        <div class="modal-content" @click.away="closeModal()">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold" x-text="showEditModal ? 'Edit Location' : 'Add New Location'"></h2>
            </div>
            <form @submit.prevent="saveLocation()" class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location Name *</label>
                    <input type="text" x-model="form.name" class="form-input" placeholder="e.g., Main Clinic, Branch Office" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                    <textarea x-model="form.address" class="form-input" rows="2" placeholder="Full address" required></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                        <input type="text" x-model="form.city" class="form-input" placeholder="City">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">PIN Code</label>
                        <input type="text" x-model="form.pincode" class="form-input" placeholder="6-digit PIN" maxlength="6">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" x-model="form.phone" class="form-input" placeholder="Phone number">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" x-model="form.email" class="form-input" placeholder="Email address">
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="form.is_primary" class="w-4 h-4 rounded border-gray-300 text-blue-600">
                        <span class="text-sm text-gray-700">Set as Primary Location</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer" x-show="showEditModal">
                        <input type="checkbox" x-model="form.is_active" class="w-4 h-4 rounded border-gray-300 text-blue-600">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="closeModal()" class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium" :disabled="saving">
                        <span x-show="!saving" x-text="showEditModal ? 'Update Location' : 'Add Location'"></span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function locationsManager() {
    return {
        showAddModal: false,
        showEditModal: false,
        saving: false,
        editingId: null,
        form: {
            name: '',
            address: '',
            city: '',
            pincode: '',
            phone: '',
            email: '',
            is_primary: false,
            is_active: true
        },

        editLocation(location) {
            console.log('Edit location:', location);
            this.editingId = location.id;
            this.form = {
                name: location.name,
                address: location.address,
                city: location.city || '',
                pincode: location.pincode || '',
                phone: location.phone || '',
                email: location.email || '',
                is_primary: location.is_primary,
                is_active: location.is_active
            };
            this.showEditModal = true;
        },

        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.editingId = null;
            this.form = {
                name: '',
                address: '',
                city: '',
                pincode: '',
                phone: '',
                email: '',
                is_primary: false,
                is_active: true
            };
        },

        async saveLocation() {
            this.saving = true;
            try {
                const url = this.showEditModal 
                    ? `/locations/${this.editingId}` 
                    : '/locations';
                const method = this.showEditModal ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                console.error('Save error:', e);
                alert('Failed to save location');
            } finally {
                this.saving = false;
            }
        },

        async deleteLocation(id) {
            if (!confirm('Are you sure you want to delete this location? This cannot be undone.')) return;

            try {
                const response = await fetch(`/locations/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                if (data.success) {
                    alert('Location deleted');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Cannot delete location'));
                }
            } catch (e) {
                console.error('Delete error:', e);
                alert('Failed to delete location');
            }
        }
    };
}
</script>
@endsection
