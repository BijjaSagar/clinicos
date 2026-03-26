@extends('layouts.app')

@section('title', 'Users & Staff')
@section('breadcrumb', 'Users & Staff')

@section('content')
<div class="p-6">
    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Users & Staff</h1>
            <p class="text-sm text-gray-500 mt-1">Manage your clinic's team members and their access</p>
        </div>
        <a href="{{ route('clinic.users.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add User
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                    <p class="text-xs text-gray-500">Total Users</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['doctors'] }}</p>
                    <p class="text-xs text-gray-500">Doctors</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['receptionists'] }}</p>
                    <p class="text-xs text-gray-500">Receptionists</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['nurses'] }}</p>
                    <p class="text-xs text-gray-500">Nurses</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['staff'] }}</p>
                    <p class="text-xs text-gray-500">Other Staff</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['active'] }}</p>
                    <p class="text-xs text-gray-500">Active</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">All Users</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0
                                    @if($user->role === 'owner') bg-gradient-to-br from-amber-500 to-orange-600
                                    @elseif($user->role === 'doctor') bg-gradient-to-br from-blue-500 to-indigo-600
                                    @elseif($user->role === 'receptionist') bg-gradient-to-br from-purple-500 to-pink-600
                                    @elseif($user->role === 'nurse') bg-gradient-to-br from-pink-500 to-rose-600
                                    @else bg-gradient-to-br from-gray-500 to-gray-600 @endif
                                ">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                    @if($user->specialty)
                                    <p class="text-xs text-gray-500">{{ $user->specialty }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full
                                @if($user->role === 'owner') bg-amber-100 text-amber-800
                                @elseif($user->role === 'doctor') bg-blue-100 text-blue-800
                                @elseif($user->role === 'receptionist') bg-purple-100 text-purple-800
                                @elseif($user->role === 'nurse') bg-pink-100 text-pink-800
                                @else bg-gray-100 text-gray-800 @endif
                            ">
                                @if($user->role === 'owner')
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a.75.75 0 01.671.415l1.941 3.935 4.341.632a.75.75 0 01.416 1.28l-3.141 3.063.741 4.325a.75.75 0 01-1.088.791L10 14.347l-3.881 2.04a.75.75 0 01-1.088-.791l.741-4.325-3.141-3.063a.75.75 0 01.416-1.28l4.341-.632L9.33 2.415A.75.75 0 0110 2z"/></svg>
                                @endif
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-900">{{ $user->email }}</p>
                            @if($user->phone)
                            <p class="text-xs text-gray-500">{{ $user->phone }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($user->is_active)
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-700 rounded-full">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                Active
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-full">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                Inactive
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-600">{{ $user->created_at->format('d M Y') }}</p>
                            <p class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1" x-data="{ showDeleteModal: false }">
                                {{-- Edit --}}
                                <a href="{{ route('clinic.users.edit', $user) }}" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                                    </svg>
                                </a>

                                @if($user->id !== auth()->id() && $user->role !== 'owner')
                                {{-- Toggle Status --}}
                                <form action="{{ route('clinic.users.toggle-status', $user) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                        @if($user->is_active)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        @endif
                                    </button>
                                </form>

                                {{-- Delete --}}
                                <button @click="showDeleteModal = true" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                </button>

                                {{-- Delete Confirmation Modal --}}
                                <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="showDeleteModal = false">
                                    <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                                    <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-md mx-4" @click.stop>
                                        <div class="text-center">
                                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                                </svg>
                                            </div>
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete User</h3>
                                            <p class="text-sm text-gray-600 mb-6">
                                                Are you sure you want to delete <strong>{{ $user->name }}</strong>? This action cannot be undone.
                                            </p>
                                            <div class="flex gap-3 justify-center">
                                                <button @click="showDeleteModal = false" class="px-4 py-2 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Cancel</button>
                                                <form action="{{ route('clinic.users.destroy', $user) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-medium transition-colors">Delete User</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <span class="text-xs text-gray-400 px-2">
                                    @if($user->id === auth()->id()) (You) @else (Owner) @endif
                                </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                            </svg>
                            <p class="text-gray-500 mb-2">No users found</p>
                            <a href="{{ route('clinic.users.create') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Add your first team member</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Role Permissions Info --}}
    <div class="mt-6 bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Role Permissions</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 bg-blue-50 rounded-xl">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">Doctor</span>
                </div>
                <ul class="text-xs text-blue-700 space-y-1">
                    <li>• View & manage patients</li>
                    <li>• Create EMR notes</li>
                    <li>• Write prescriptions</li>
                    <li>• View billing</li>
                </ul>
            </div>
            <div class="p-4 bg-purple-50 rounded-xl">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 text-xs font-semibold bg-purple-100 text-purple-800 rounded-full">Receptionist</span>
                </div>
                <ul class="text-xs text-purple-700 space-y-1">
                    <li>• Schedule appointments</li>
                    <li>• Register patients</li>
                    <li>• Create invoices</li>
                    <li>• Collect payments</li>
                </ul>
            </div>
            <div class="p-4 bg-pink-50 rounded-xl">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 text-xs font-semibold bg-pink-100 text-pink-800 rounded-full">Nurse</span>
                </div>
                <ul class="text-xs text-pink-700 space-y-1">
                    <li>• View patient records</li>
                    <li>• Record vitals</li>
                    <li>• Assist with procedures</li>
                    <li>• View schedule</li>
                </ul>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-800 rounded-full">Staff</span>
                </div>
                <ul class="text-xs text-gray-700 space-y-1">
                    <li>• View dashboard</li>
                    <li>• View schedule</li>
                    <li>• Limited access</li>
                    <li>• Basic features</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
