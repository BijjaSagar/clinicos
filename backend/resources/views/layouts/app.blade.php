@php
/**
 * Helper: check if a named route exists before calling route()
 */
if (!function_exists('route_exists')) {
    function route_exists(string $name): bool {
        return \Illuminate\Support\Facades\Route::has($name);
    }
}
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'ClinicOS') — ClinicOS</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Sora', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            blue: '#1447E6',
                            'blue-dark': '#0f35b8',
                            'blue-light': '#eff3ff',
                            teal: '#0891B2',
                            green: '#059669',
                        },
                        sidebar: '#0D1117',
                        'sidebar-2': '#161b27',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Alpine.js x-cloak style -->
    <style>
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-50 font-sans antialiased" x-data="{ sidebarOpen: true }">

{{-- Impersonation Banner --}}
@if(session('impersonating_from'))
<div class="fixed top-0 left-0 right-0 z-[9999] bg-indigo-600 text-white px-4 py-2">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
            </svg>
            <span class="text-sm font-medium">You are viewing as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->clinic?->name ?? 'N/A' }})</span>
        </div>
        <a href="{{ route('admin.stop-impersonating') }}" class="inline-flex items-center gap-2 px-4 py-1.5 bg-white text-indigo-600 text-sm font-semibold rounded-lg hover:bg-indigo-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
            </svg>
            Return to Admin
        </a>
    </div>
</div>
<div class="h-10"></div>
@endif

{{-- Flash Messages --}}
@if(session('success'))
<div
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 4500)"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    class="fixed top-4 right-4 z-[9999] flex items-center gap-3 bg-white border border-green-200 shadow-lg rounded-xl px-5 py-3.5 min-w-[300px]"
>
    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    </div>
    <div class="flex-1">
        <p class="text-sm font-600 text-gray-900">{{ session('success') }}</p>
    </div>
    <button @click="show = false" class="text-gray-400 hover:text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
@endif

@if(session('error'))
<div
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 5000)"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    class="fixed top-4 right-4 z-[9999] flex items-center gap-3 bg-white border border-red-200 shadow-lg rounded-xl px-5 py-3.5 min-w-[300px]"
>
    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </div>
    <div class="flex-1">
        <p class="text-sm font-semibold text-gray-900">{{ session('error') }}</p>
    </div>
    <button @click="show = false" class="text-gray-400 hover:text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
@endif

<div class="flex h-screen overflow-hidden">

    {{-- ══════════════════════════════════════════
         SIDEBAR
    ══════════════════════════════════════════ --}}
    <aside
        :class="sidebarOpen ? 'w-64' : 'w-16'"
        class="flex-shrink-0 flex flex-col h-full transition-all duration-300 ease-in-out overflow-hidden"
        style="background-color: #0D1117;"
    >
        {{-- Clinic Switcher --}}
        <div class="px-3 pt-4 pb-2 border-b border-white/[0.06]" x-data="{ open: false }">
            <button
                @click="open = !open"
                :class="sidebarOpen ? 'px-3' : 'px-2 justify-center'"
                class="relative w-full flex items-center gap-2.5 py-2.5 rounded-xl hover:bg-white/[0.06] transition-colors group"
            >
                {{-- Logo Mark --}}
                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center font-bold text-white text-sm font-display"
                     style="background: linear-gradient(135deg, #1447E6 0%, #0891B2 100%);">
                    C
                </div>
                <div x-show="sidebarOpen" class="flex-1 text-left min-w-0">
                    <div class="text-white font-semibold text-sm truncate leading-tight">
                        {{ auth()->user()?->clinic?->name ?? 'ClinicOS' }}
                    </div>
                    <div class="text-gray-500 text-xs truncate mt-0.5">
                        {{ auth()->user()?->clinic?->specialty ?? 'Specialty Clinic' }}
                    </div>
                </div>
                <svg x-show="sidebarOpen" class="w-4 h-4 text-gray-500 flex-shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Dropdown --}}
            <div
                x-show="open && sidebarOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                @click.away="open = false"
                class="mt-1 rounded-xl overflow-hidden border border-white/[0.08]"
                style="background-color: #161b27;"
            >
                @php
                    $userClinics = auth()->user()?->clinics ?? collect();
                @endphp
                @foreach($userClinics as $clinic)
                <a href="#" class="flex items-center gap-2.5 px-3 py-2.5 hover:bg-white/[0.05] transition-colors">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                         style="background: linear-gradient(135deg, #1447E6, #0891B2);">
                        {{ substr($clinic->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="text-white text-xs font-semibold">{{ $clinic->name }}</div>
                        <div class="text-gray-500 text-xs">{{ $clinic->specialty }}</div>
                    </div>
                </a>
                @endforeach
                <div class="border-t border-white/[0.06] px-3 py-2">
                    <a href="#" class="flex items-center gap-2 text-xs text-blue-400 hover:text-blue-300 font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add new clinic
                    </a>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-2 overflow-y-auto">
            @php
                $currentRoute = request()->route()?->getName() ?? '';
                $userRole = auth()->user()?->role ?? 'staff';
                
                // Define which roles can access which menu items
                $roleAccess = [
                    'owner' => ['all'], // Owner has access to everything
                    'doctor' => ['dashboard', 'schedule', 'patients', 'emr', 'whatsapp', 'billing', 'photo-vault', 'prescriptions', 'vendor', 'analytics'],
                    'receptionist' => ['dashboard', 'schedule', 'patients', 'whatsapp', 'billing', 'payments', 'gst-reports'],
                    'nurse' => ['dashboard', 'schedule', 'patients', 'emr', 'photo-vault', 'prescriptions'],
                    'staff' => ['dashboard', 'schedule'],
                ];
                
                $userAccess = $roleAccess[$userRole] ?? $roleAccess['staff'];
                $hasAllAccess = in_array('all', $userAccess);
                
                // Helper to check access
                $canAccess = function($key) use ($userAccess, $hasAllAccess) {
                    return $hasAllAccess || in_array($key, $userAccess);
                };
                
                $navSections = [];
                
                // CLINIC Section
                $clinicItems = [];
                $clinicItems[] = ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>', 'badge' => null, 'key' => 'dashboard'];
                $clinicItems[] = ['route' => 'schedule', 'label' => 'Schedule', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>', 'badge' => '14', 'badgeColor' => 'blue', 'key' => 'schedule'];
                if ($canAccess('patients')) {
                    $clinicItems[] = ['route' => 'patients.index', 'label' => 'Patients', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>', 'badge' => null, 'key' => 'patients'];
                }
                if ($canAccess('emr')) {
                    $clinicItems[] = ['route' => 'emr.index', 'label' => 'EMR / Notes', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>', 'badge' => null, 'key' => 'emr'];
                }
                if ($canAccess('whatsapp')) {
                    $clinicItems[] = ['route' => 'whatsapp.index', 'label' => 'WhatsApp', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>', 'badge' => '3', 'badgeColor' => 'red', 'key' => 'whatsapp'];
                }
                $navSections[] = ['label' => 'Clinic', 'items' => $clinicItems];
                
                // BILLING Section (only if user has access to any billing feature)
                if ($canAccess('billing') || $canAccess('payments') || $canAccess('gst-reports')) {
                    $billingItems = [];
                    if ($canAccess('billing')) {
                        $billingItems[] = ['route' => 'billing.index', 'label' => 'Invoices', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>', 'badge' => null, 'key' => 'billing'];
                    }
                    if ($canAccess('payments')) {
                        $billingItems[] = ['route' => 'payments.index', 'label' => 'Payments', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>', 'badge' => null, 'key' => 'payments'];
                    }
                    if ($canAccess('gst-reports')) {
                        $billingItems[] = ['route' => 'gst-reports.index', 'label' => 'GST Reports', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>', 'badge' => null, 'key' => 'gst-reports'];
                    }
                    if (count($billingItems) > 0) {
                        $navSections[] = ['label' => 'Billing', 'items' => $billingItems];
                    }
                }
                
                // CLINICAL Section (only if user has access to any clinical feature)
                if ($canAccess('photo-vault') || $canAccess('prescriptions') || $canAccess('vendor')) {
                    $clinicalItems = [];
                    if ($canAccess('photo-vault')) {
                        $clinicalItems[] = ['route' => 'photo-vault.index', 'label' => 'Photo Vault', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>', 'badge' => null, 'key' => 'photo-vault'];
                    }
                    if ($canAccess('prescriptions')) {
                        $clinicalItems[] = ['route' => 'prescriptions.index', 'label' => 'Prescriptions', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>', 'badge' => null, 'key' => 'prescriptions'];
                    }
                    if ($canAccess('vendor')) {
                        $clinicalItems[] = ['route' => 'vendor.index', 'label' => 'Lab Orders', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>', 'badge' => null, 'key' => 'vendor'];
                    }
                    if (count($clinicalItems) > 0) {
                        $navSections[] = ['label' => 'Clinical', 'items' => $clinicalItems];
                    }
                }
                
                // ADMIN Section (only for owner and doctor roles)
                if (in_array($userRole, ['owner', 'doctor'])) {
                    $adminItems = [];
                    // Users & Staff - ONLY for owner
                    if ($userRole === 'owner') {
                        $adminItems[] = ['route' => 'clinic.users.index', 'label' => 'Users & Staff', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>', 'badge' => null, 'key' => 'users'];
                    }
                    $adminItems[] = ['route' => 'abdm.index', 'label' => 'ABDM Centre', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>', 'badge' => null, 'key' => 'abdm'];
                    if ($canAccess('analytics')) {
                        $adminItems[] = ['route' => 'analytics.index', 'label' => 'Analytics', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>', 'badge' => null, 'key' => 'analytics'];
                    }
                    // Settings - ONLY for owner
                    if ($userRole === 'owner') {
                        $adminItems[] = ['route' => 'settings.index', 'label' => 'Settings', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>', 'badge' => null, 'key' => 'settings'];
                    }
                    if (count($adminItems) > 0) {
                        $navSections[] = ['label' => 'Admin', 'items' => $adminItems];
                    }
                }
            @endphp

            @foreach($navSections as $section)
            <div class="mb-4">
                {{-- Section Label --}}
                <div x-show="sidebarOpen" class="px-3 mb-2 mt-3 first:mt-0">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">
                        {{ $section['label'] }}
                    </span>
                </div>
                
                <div class="space-y-0.5">
                    @foreach($section['items'] as $item)
                    @php
                        $isActive = str_starts_with($currentRoute, explode('.', $item['route'])[0]);
                    @endphp
                    <a href="{{ route_exists($item['route']) ? route($item['route']) : '#' }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-150 group relative
                              {{ $isActive
                                  ? 'text-blue-300 bg-blue-900/30'
                                  : 'text-gray-400 hover:text-gray-200 hover:bg-white/[0.04]' }}"
                       :class="sidebarOpen ? '' : 'justify-center'"
                    >
                        {{-- Active left border indicator --}}
                        @if($isActive)
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 rounded-r-full bg-blue-400"></div>
                        @endif
                        <svg class="flex-shrink-0 w-[18px] h-[18px] {{ $isActive ? 'text-blue-400' : 'text-gray-500 group-hover:text-gray-400' }}"
                             fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            {!! $item['icon'] !!}
                        </svg>
                        <span x-show="sidebarOpen" class="truncate flex-1">
                            {{ $item['label'] }}
                        </span>

                        {{-- Badge --}}
                        @if(!empty($item['badge']))
                        <span x-show="sidebarOpen" class="px-1.5 py-0.5 text-[10px] font-bold rounded-full {{ ($item['badgeColor'] ?? 'blue') === 'red' ? 'bg-red-500 text-white' : 'bg-blue-500 text-white' }}">
                            {{ $item['badge'] }}
                        </span>
                        @endif

                        {{-- Tooltip when collapsed --}}
                        <div x-show="!sidebarOpen"
                             class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded-lg whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 border border-white/10">
                            {{ $item['label'] }}
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endforeach
        </nav>

        {{-- Doctor Profile at Bottom --}}
        <div class="border-t border-white/[0.06] p-3" x-data="{ menuOpen: false }">
            <button
                @click="menuOpen = !menuOpen"
                :class="sidebarOpen ? 'px-3' : 'px-2 justify-center'"
                class="relative w-full flex items-center gap-2.5 py-2 rounded-xl hover:bg-white/[0.06] transition-colors"
            >
                {{-- Avatar --}}
                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white"
                     style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    {{ strtoupper(substr(auth()->user()?->name ?? 'D', 0, 1)) }}
                </div>
                <div x-show="sidebarOpen" class="flex-1 text-left min-w-0">
                    <div class="text-white text-xs font-semibold truncate">{{ auth()->user()?->name ?? 'Doctor' }}</div>
                    <div class="text-gray-500 text-xs truncate">{{ auth()->user()?->role ?? 'Physician' }}</div>
                </div>
                <svg x-show="sidebarOpen" class="w-4 h-4 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01"/>
                </svg>
            </button>

            {{-- Profile menu --}}
            <div
                x-show="menuOpen && sidebarOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                @click.away="menuOpen = false"
                class="mt-1 rounded-xl overflow-hidden border border-white/[0.08] text-sm"
                style="background-color: #161b27;"
            >
                <a href="{{ route_exists('profile') ? route('profile') : '#' }}" class="flex items-center gap-2.5 px-3 py-2.5 text-gray-400 hover:text-white hover:bg-white/[0.05] transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    My Profile
                </a>
                <a href="{{ route_exists('settings') ? route('settings') : '#' }}" class="flex items-center gap-2.5 px-3 py-2.5 text-gray-400 hover:text-white hover:bg-white/[0.05] transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Settings
                </a>
                <div class="border-t border-white/[0.06]">
                    <form method="POST" action="{{ route_exists('logout') ? route('logout') : '/logout' }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2.5 text-red-400 hover:text-red-300 hover:bg-white/[0.05] transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar toggle button --}}
        <button
            @click="sidebarOpen = !sidebarOpen"
            class="absolute bottom-20 -right-3 w-6 h-6 bg-gray-700 border border-gray-600 rounded-full flex items-center justify-center hover:bg-gray-600 transition-colors z-10"
            style="position: absolute; bottom: 80px;"
        >
            <svg :class="sidebarOpen ? '' : 'rotate-180'" class="w-3 h-3 text-gray-300 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
    </aside>

    {{-- ══════════════════════════════════════════
         MAIN CONTENT AREA
    ══════════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top Header Bar --}}
        <header class="flex-shrink-0 bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between gap-4">
            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 text-sm min-w-0">
                <a href="{{ route_exists('dashboard') ? route('dashboard') : '#' }}" class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </a>
                @hasSection('breadcrumb')
                <svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-600 font-medium truncate">@yield('breadcrumb')</span>
                @endif
            </div>

            {{-- Right side controls --}}
            <div class="flex items-center gap-3 flex-shrink-0">
                {{-- Clinic name pill --}}
                <div class="hidden md:flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 border border-blue-100 rounded-full">
                    <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                    <span class="text-blue-700 text-xs font-semibold">{{ auth()->user()?->clinic?->name ?? 'ClinicOS' }}</span>
                </div>

                {{-- Search button --}}
                <button class="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500 hover:text-gray-700">
                    <svg class="w-4.5 h-4.5 w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>

                {{-- Notification bell --}}
                <button class="relative p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500 hover:text-gray-700">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    {{-- Badge --}}
                    @php $notifCount = 3; @endphp
                    @if($notifCount > 0)
                    <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                        {{ $notifCount > 9 ? '9+' : $notifCount }}
                    </span>
                    @endif
                </button>

                {{-- Doctor avatar --}}
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0 cursor-pointer"
                     style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    {{ strtoupper(substr(auth()->user()?->name ?? 'D', 0, 1)) }}
                </div>
            </div>
        </header>

        {{-- Page Content --}}
        <main class="flex-1 overflow-y-auto">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="flex-shrink-0 bg-white border-t border-gray-100 px-6 py-2">
            <p class="text-xs text-gray-400 text-center">
                ClinicOS v2.0 &middot; PHP {{ PHP_VERSION }} &middot; Laravel {{ app()->version() }}
            </p>
        </footer>
    </div>
</div>

@stack('scripts')
</body>
</html>
