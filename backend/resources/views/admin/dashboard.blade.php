@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Overview of your ClinicOS platform')

@section('content')
<div class="space-y-6">
    {{-- Error Banner --}}
    @if(isset($error))
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <p class="text-sm text-red-700">{{ $error }}</p>
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Total Clinics --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 card-hover">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Clinics</p>
                    <p class="text-3xl font-display font-bold text-slate-900 mt-2">{{ number_format($stats['total_clinics']) }}</p>
                    <div class="mt-3 flex items-center gap-3 text-xs">
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-50 text-emerald-700 rounded-full font-medium">
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                            {{ $stats['active_clinics'] }} active
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-amber-50 text-amber-700 rounded-full font-medium">
                            <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                            {{ $stats['trial_clinics'] }} trial
                        </span>
                    </div>
                </div>
                <div class="w-14 h-14 gradient-indigo rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Users --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 card-hover">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Users</p>
                    <p class="text-3xl font-display font-bold text-slate-900 mt-2">{{ number_format($stats['total_users']) }}</p>
                    <p class="mt-3 text-xs text-slate-500">Across all clinics</p>
                </div>
                <div class="w-14 h-14 gradient-blue rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Patients --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 card-hover">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Patients</p>
                    <p class="text-3xl font-display font-bold text-slate-900 mt-2">{{ number_format($stats['total_patients']) }}</p>
                    <p class="mt-3 text-xs text-slate-500">{{ number_format($stats['total_appointments']) }} appointments</p>
                </div>
                <div class="w-14 h-14 gradient-green rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/30">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- This Month Revenue --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 card-hover">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Platform Revenue</p>
                    <p class="text-3xl font-display font-bold text-slate-900 mt-2">₹{{ number_format($stats['this_month_revenue']) }}</p>
                    <p class="mt-3 text-xs text-slate-500">This month's revenue</p>
                </div>
                <div class="w-14 h-14 gradient-purple rounded-2xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Clinics by Plan --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
            <h3 class="text-lg font-display font-bold text-slate-900 mb-5">Clinics by Plan</h3>
            <div class="space-y-4">
                @php
                    $plans = ['trial' => 'Trial', 'solo' => 'Solo', 'small' => 'Small', 'group' => 'Group', 'enterprise' => 'Enterprise'];
                    $planStyles = [
                        'trial' => ['bg' => 'bg-amber-500', 'light' => 'bg-amber-100'],
                        'solo' => ['bg' => 'bg-blue-500', 'light' => 'bg-blue-100'],
                        'small' => ['bg' => 'bg-emerald-500', 'light' => 'bg-emerald-100'],
                        'group' => ['bg' => 'bg-purple-500', 'light' => 'bg-purple-100'],
                        'enterprise' => ['bg' => 'bg-indigo-500', 'light' => 'bg-indigo-100'],
                    ];
                    $totalClinics = max(array_sum($clinicsByPlan ?? []), 1);
                @endphp
                @foreach($plans as $key => $label)
                    @php 
                        $count = $clinicsByPlan[$key] ?? 0;
                        $percentage = round(($count / $totalClinics) * 100);
                        $style = $planStyles[$key] ?? ['bg' => 'bg-gray-500', 'light' => 'bg-gray-100'];
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full {{ $style['bg'] }}"></span>
                                <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                            </div>
                            <span class="text-sm font-bold text-slate-900">{{ $count }}</span>
                        </div>
                        <div class="w-full h-2 {{ $style['light'] }} rounded-full overflow-hidden">
                            <div class="{{ $style['bg'] }} h-full rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Monthly Signups Chart --}}
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
            <h3 class="text-lg font-display font-bold text-slate-900 mb-5">Monthly Signups</h3>
            <canvas id="signupsChart" height="140"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        {{-- Recent Clinics --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Recent Signups</h3>
                <a href="{{ route('admin.clinics.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700">View All</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($recentClinics as $clinic)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <span class="text-indigo-600 font-semibold">{{ substr($clinic->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $clinic->name }}</p>
                            <p class="text-xs text-gray-500">{{ $clinic->owner?->name ?? 'No owner' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                            @if($clinic->plan === 'trial') bg-amber-100 text-amber-700
                            @elseif($clinic->plan === 'solo') bg-blue-100 text-blue-700
                            @elseif($clinic->plan === 'small') bg-green-100 text-green-700
                            @elseif($clinic->plan === 'group') bg-purple-100 text-purple-700
                            @else bg-indigo-100 text-indigo-700 @endif
                        ">{{ ucfirst($clinic->plan) }}</span>
                        <p class="text-xs text-gray-500 mt-1">{{ $clinic->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    No clinics yet
                </div>
                @endforelse
            </div>
        </div>

        {{-- Trials Expiring Soon --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Trials Expiring Soon</h3>
                <span class="text-sm text-gray-500">Next 7 days</span>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($expiringSoon as $clinic)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $clinic->name }}</p>
                            <p class="text-xs text-gray-500">{{ $clinic->owner?->email ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-amber-600">{{ $clinic->trial_ends_at->diffForHumans() }}</p>
                        <a href="{{ route('admin.clinics.show', $clinic) }}" class="text-xs text-indigo-600 hover:underline">View</a>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p>No trials expiring soon</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="flex gap-4">
            <a href="{{ route('admin.clinics.create') }}" class="flex items-center gap-3 px-5 py-3 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition-colors">
                <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Create New Clinic</p>
                    <p class="text-xs text-gray-500">Onboard a new clinic</p>
                </div>
            </a>

            <a href="{{ route('admin.users.create') }}" class="flex items-center gap-3 px-5 py-3 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Add New User</p>
                    <p class="text-xs text-gray-500">Create admin or staff</p>
                </div>
            </a>

            <a href="{{ route('admin.clinics.index') }}?status=expired" class="flex items-center gap-3 px-5 py-3 bg-amber-50 hover:bg-amber-100 rounded-xl transition-colors">
                <div class="w-10 h-10 bg-amber-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Expired Trials</p>
                    <p class="text-xs text-gray-500">{{ $stats['expired_trials'] }} clinics</p>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ctx = document.getElementById('signupsChart').getContext('2d');
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.9)');
    gradient.addColorStop(1, 'rgba(79, 70, 229, 0.6)');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(collect($monthlySignups)->pluck('month')) !!},
            datasets: [{
                label: 'New Clinics',
                data: {!! json_encode(collect($monthlySignups)->pluck('count')) !!},
                backgroundColor: gradient,
                borderRadius: 10,
                barThickness: 45,
                hoverBackgroundColor: 'rgba(79, 70, 229, 1)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { family: 'Inter', weight: '600' },
                    bodyFont: { family: 'Inter' },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { 
                        precision: 0,
                        font: { family: 'Inter', size: 12 },
                        color: '#94a3b8'
                    },
                    grid: {
                        color: 'rgba(148, 163, 184, 0.1)',
                        drawBorder: false,
                    }
                },
                x: {
                    ticks: {
                        font: { family: 'Inter', size: 12 },
                        color: '#64748b'
                    },
                    grid: { display: false }
                }
            }
        }
    });
</script>
@endpush
