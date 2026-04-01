<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - ClinicOS Admin</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                        sidebar: {
                            DEFAULT: '#0f172a',
                            light: '#1e293b',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Sidebar styles */
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.15s ease;
            color: #94a3b8;
        }
        .sidebar-link:hover {
            background: rgba(255,255,255,0.08);
            color: #e2e8f0;
        }
        .sidebar-link.active {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        /* Card hover effect */
        .card-hover {
            transition: all 0.2s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -8px rgba(0,0,0,0.1);
        }
        
        /* Gradient backgrounds */
        .gradient-indigo { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
        .gradient-blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .gradient-green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .gradient-purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        .gradient-amber { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50 antialiased" x-data="{ adminMobileNav: false }" x-init="window.addEventListener('resize', () => { if (window.matchMedia('(min-width: 1024px)').matches) adminMobileNav = false })" :class="adminMobileNav ? 'overflow-hidden lg:overflow-auto' : ''">
    {{-- Flash Messages --}}
    @if(session('success'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 4500)"
        x-transition
        class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 z-[9999] flex items-center gap-3 bg-white border border-green-200 shadow-lg rounded-xl px-4 sm:px-5 py-3.5 max-w-[min(100%,24rem)] sm:min-w-[280px] sm:max-w-none mx-auto sm:mx-0"
    >
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-gray-900">{{ session('success') }}</p>
        <button @click="show = false" class="ml-auto text-gray-400 hover:text-gray-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 5000)"
        x-transition
        class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 z-[9999] flex items-center gap-3 bg-white border border-red-200 shadow-lg rounded-xl px-4 sm:px-5 py-3.5 max-w-[min(100%,24rem)] sm:min-w-[280px] sm:max-w-none mx-auto sm:mx-0"
    >
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-gray-900">{{ session('error') }}</p>
        <button @click="show = false" class="ml-auto text-gray-400 hover:text-gray-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    @endif

    @if(session('info'))
    <div
        x-data="{ show: true }"
        x-show="show"
        class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 z-[9999] flex flex-col sm:flex-row sm:items-center gap-3 bg-indigo-600 text-white shadow-lg rounded-xl px-4 sm:px-5 py-3.5 max-w-[min(100%,24rem)] sm:min-w-[280px] sm:max-w-none mx-auto sm:mx-0"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm font-medium">{{ session('info') }}</p>
        <a href="{{ route('admin.stop-impersonating') }}" class="ml-auto px-3 py-1 bg-white text-indigo-600 rounded-lg text-xs font-semibold hover:bg-indigo-50">
            Return to Admin
        </a>
    </div>
    @endif

    <div class="flex min-h-screen w-full min-w-0 relative">
        <div
            x-show="adminMobileNav"
            x-transition.opacity.duration.200ms
            @click="adminMobileNav = false"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            x-cloak
        ></div>

        <!-- Sidebar -->
        <aside
            class="w-72 max-w-[min(18rem,88vw)] bg-sidebar text-white flex flex-col fixed h-full z-50 transition-transform duration-300 ease-out
                   max-lg:shadow-2xl
                   lg:translate-x-0"
            :class="adminMobileNav ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            @click.capture="(function (el) { if (!el) return; var h = el.getAttribute('href'); if (h && h.indexOf('#') !== 0) adminMobileNav = false; })($event.target.closest('a[href]'))"
        >
            <!-- Logo -->
            <div class="px-6 py-6">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 gradient-indigo rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <span class="text-white font-bold text-xl font-display">C</span>
                    </div>
                    <div>
                        <h1 class="font-display font-bold text-xl tracking-tight">ClinicOS</h1>
                        <p class="text-slate-400 text-xs font-medium">Super Admin Portal</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-2 space-y-1 overflow-y-auto">
                <p class="px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-widest">Overview</p>
                
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                    </svg>
                    Dashboard
                </a>

                <p class="px-4 py-3 mt-2 text-[11px] font-semibold text-slate-500 uppercase tracking-widest">Management</p>
                
                <a href="{{ route('admin.clinics.index') }}" class="sidebar-link {{ request()->routeIs('admin.clinics*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
                    </svg>
                    Clinics
                </a>

                <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                    Users
                </a>

                <p class="px-4 py-3 mt-2 text-[11px] font-semibold text-slate-500 uppercase tracking-widest">Billing</p>
                
                <a href="{{ route('admin.subscriptions.index') }}" class="sidebar-link {{ request()->routeIs('admin.subscriptions*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                    </svg>
                    Subscriptions
                </a>

                <p class="px-4 py-3 mt-2 text-[11px] font-semibold text-slate-500 uppercase tracking-widest">System</p>
                
                <a href="{{ route('admin.settings.index') }}" class="sidebar-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Settings
                </a>
            </nav>

            <!-- User Profile -->
            <div class="p-4 mx-3 mb-3 bg-sidebar-light rounded-xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 gradient-indigo rounded-xl flex items-center justify-center text-sm font-bold shadow-lg shadow-indigo-500/30">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-400 truncate">Super Admin</p>
                    </div>
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-700/50 transition-colors" title="Logout">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 w-full min-w-0 bg-slate-50 min-h-screen lg:ml-72">
            <!-- Header -->
            <header class="bg-white/80 backdrop-blur-lg border-b border-slate-200/80 sticky top-0 z-30">
                <div class="px-4 sm:px-6 lg:px-8 py-4 sm:py-5 flex items-start sm:items-center justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0 flex-1">
                        <button
                            type="button"
                            @click="adminMobileNav = true"
                            class="lg:hidden flex-shrink-0 mt-0.5 p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                            aria-label="Open menu"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <div class="min-w-0">
                        <h2 class="text-lg sm:text-2xl font-display font-bold text-slate-900 truncate">@yield('title', 'Dashboard')</h2>
                        @hasSection('subtitle')
                            <p class="text-xs sm:text-sm text-slate-500 mt-1 line-clamp-2 sm:line-clamp-none">@yield('subtitle')</p>
                        @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0">
                        @yield('header_actions')
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-4 sm:p-6 lg:p-8 max-w-full overflow-x-auto">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
