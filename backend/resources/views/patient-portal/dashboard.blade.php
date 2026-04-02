<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal — {{ $patient->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">

{{-- Header --}}
<header class="bg-white border-b border-gray-200 sticky top-0 z-10">
    <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-900">{{ $patient->name }}</p>
                <p class="text-xs text-gray-400">Patient Portal</p>
            </div>
        </div>
        <form action="{{ route('patient-portal.logout') }}" method="POST">
            @csrf
            <button type="submit" class="text-xs text-gray-500 hover:text-red-600 font-medium px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors">
                Log out
            </button>
        </form>
    </div>
</header>

<main class="max-w-3xl mx-auto px-4 py-6 space-y-5">

    {{-- Welcome --}}
    <div>
        <h1 class="text-xl font-bold text-gray-900">Hello, {{ explode(' ', $patient->name)[0] }}!</h1>
        <p class="text-sm text-gray-500 mt-0.5">Your health records at a glance.</p>
    </div>

    @if($pendingBalance > 0)
    <div class="flex items-center gap-3 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <span>You have a pending balance of <strong>₹{{ number_format($pendingBalance, 2) }}</strong>. Please settle it at your next visit.</span>
    </div>
    @endif

    {{-- Quick Links --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @php
        $links = [
            ['label' => 'Appointments', 'url' => route('patient-portal.appointments'), 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'blue'],
            ['label' => 'Prescriptions', 'url' => route('patient-portal.prescriptions'), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'purple'],
            ['label' => 'Lab Reports', 'url' => route('patient-portal.lab-reports'), 'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', 'color' => 'green'],
            ['label' => 'Invoices', 'url' => route('patient-portal.invoices'), 'icon' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z', 'color' => 'amber'],
        ];
        $colorMap = [
            'blue'   => 'bg-blue-50 text-blue-700',
            'purple' => 'bg-purple-50 text-purple-700',
            'green'  => 'bg-green-50 text-green-700',
            'amber'  => 'bg-amber-50 text-amber-700',
        ];
        @endphp
        @foreach($links as $link)
        <a href="{{ $link['url'] }}"
           class="flex flex-col items-center gap-2 p-4 bg-white border border-gray-100 rounded-2xl hover:shadow-sm transition-shadow text-center">
            <div class="w-10 h-10 rounded-xl {{ $colorMap[$link['color']] }} flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-gray-700">{{ $link['label'] }}</span>
        </a>
        @endforeach
    </div>

    {{-- Recent Appointments --}}
    @if($appointments->isNotEmpty())
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900 text-sm">Recent Appointments</h2>
            <a href="{{ route('patient-portal.appointments') }}" class="text-xs text-blue-600 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($appointments as $apt)
            <div class="px-5 py-3 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-900">
                        {{ \Carbon\Carbon::parse($apt->scheduled_at)->format('d M Y') }}
                    </p>
                    <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($apt->scheduled_at)->format('h:i A') }}</p>
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
            @endforeach
        </div>
    </div>
    @endif

    @if($labReportCount > 0)
    <a href="{{ route('patient-portal.lab-reports') }}"
       class="flex items-center justify-between px-5 py-4 bg-white border border-gray-100 rounded-2xl hover:shadow-sm transition-shadow">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-green-100 text-green-700 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900">{{ $labReportCount }} lab result{{ $labReportCount > 1 ? 's' : '' }} available</p>
                <p class="text-xs text-gray-400">Tap to view and download</p>
            </div>
        </div>
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
    @endif

</main>
</body>
</html>
