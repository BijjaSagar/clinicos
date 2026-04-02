<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">
<header class="bg-white border-b border-gray-200 sticky top-0 z-10">
    <div class="max-w-3xl mx-auto px-4 py-3 flex items-center gap-3">
        <a href="{{ route('patient-portal.dashboard') }}" class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-base font-bold text-gray-900">My Appointments</h1>
    </div>
</header>
<main class="max-w-3xl mx-auto px-4 py-5">
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
        <div class="divide-y divide-gray-50">
            @forelse($appointments as $apt)
            <div class="px-5 py-4 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ \Carbon\Carbon::parse($apt->scheduled_at)->format('d M Y, h:i A') }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ ucfirst(str_replace('_', ' ', $apt->appointment_type ?? 'Consultation')) }}</p>
                </div>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full
                    {{ match($apt->status) {
                        'completed' => 'bg-green-100 text-green-700',
                        'cancelled', 'no_show' => 'bg-red-100 text-red-600',
                        'checked_in', 'in_consultation' => 'bg-blue-100 text-blue-700',
                        default => 'bg-gray-100 text-gray-600',
                    } }}">
                    {{ ucfirst(str_replace('_', ' ', $apt->status)) }}
                </span>
            </div>
            @empty
            <div class="px-5 py-10 text-center text-gray-400 text-sm">No appointments found.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-4">{{ $appointments->links() }}</div>
</main>
</body>
</html>
