<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Sign In') — ClinicOS</title>
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
                }
            }
        }
    </script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
</head>
<body class="font-sans antialiased min-h-screen" style="background: linear-gradient(135deg, #0f35b8 0%, #1447E6 40%, #0891B2 100%);">

    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12 relative overflow-hidden">

        {{-- Background decorative circles --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full opacity-10" style="background: radial-gradient(circle, white, transparent);"></div>
            <div class="absolute -bottom-32 -right-32 w-[500px] h-[500px] rounded-full opacity-10" style="background: radial-gradient(circle, white, transparent);"></div>
            <div class="absolute top-1/2 left-1/4 w-48 h-48 rounded-full opacity-5" style="background: radial-gradient(circle, white, transparent);"></div>
        </div>

        {{-- Logo --}}
        <div class="mb-8 flex flex-col items-center">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-lg font-display shadow-lg"
                     style="background: rgba(255,255,255,0.2); border: 1.5px solid rgba(255,255,255,0.3);">
                    C
                </div>
                <div>
                    <div class="text-white font-bold text-xl font-display leading-tight">ClinicOS</div>
                    <div class="text-blue-200 text-xs">क्लिनिक ओएस</div>
                </div>
            </div>
            <div class="flex items-center gap-1.5 mt-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span class="text-blue-100 text-xs font-medium">ABDM Compliant · Specialty-First EMR</span>
            </div>
        </div>

        {{-- Card --}}
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
            @yield('content')
        </div>

        {{-- Footer --}}
        <p class="mt-8 text-blue-200 text-xs text-center">
            &copy; 2026 RH Technology, Pune &middot; ABDM Compliant
        </p>
    </div>

    @stack('scripts')
</body>
</html>
