<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Queue — {{ $clinic->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-950 text-white min-h-screen font-sans antialiased"
      x-data="{ lastUpdate: new Date().toLocaleTimeString() }"
      x-init="setInterval(() => { fetch(window.location.href, {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(() => lastUpdate = new Date().toLocaleTimeString()); }, 30000)">

<div class="max-w-lg mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-white">{{ $clinic->name }}</h1>
        <p class="text-gray-400 text-sm mt-1">Live OPD Queue · {{ today()->format('d F Y') }}</p>
    </div>

    {{-- Now Serving --}}
    <div class="rounded-2xl mb-6 overflow-hidden" style="background:linear-gradient(135deg,#1447E6,#0891B2)">
        <div class="px-6 py-8 text-center">
            <p class="text-blue-200 text-sm font-semibold uppercase tracking-widest mb-2">Now Serving</p>
            @if($serving)
                <div class="text-8xl font-black my-4">{{ $serving->queue_token }}</div>
                <p class="text-blue-100 text-sm">{{ \Carbon\Carbon::parse($serving->scheduled_at)->format('h:i A') }}</p>
            @else
                <div class="text-5xl font-black my-4 text-blue-300">—</div>
                <p class="text-blue-200 text-sm">Queue not started yet</p>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-gray-800 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-white">{{ $waiting->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">Waiting</p>
        </div>
        <div class="bg-gray-800 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-400">{{ $completedToday }}</p>
            <p class="text-xs text-gray-400 mt-1">Completed</p>
        </div>
        <div class="bg-gray-800 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-400">{{ $totalToday }}</p>
            <p class="text-xs text-gray-400 mt-1">Total Today</p>
        </div>
    </div>

    {{-- Waiting Queue --}}
    @if($waiting->isNotEmpty())
    <div class="bg-gray-800 rounded-2xl overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-gray-700">
            <h2 class="text-sm font-semibold text-gray-300">Waiting Queue</h2>
        </div>
        <div class="divide-y divide-gray-700">
            @foreach($waiting as $i => $patient)
            <div class="flex items-center gap-4 px-5 py-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg
                    {{ $i === 0 ? 'bg-amber-500 text-white' : 'bg-gray-700 text-gray-300' }}">
                    {{ $patient->queue_token }}
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-white">{{ Str::before($patient->patient_name, ' ') }}…</p>
                    <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($patient->scheduled_at)->format('h:i A') }}</p>
                </div>
                @if($i === 0)
                <span class="text-xs text-amber-400 font-semibold">Next</span>
                @else
                <span class="text-xs text-gray-500">~{{ $i * 15 }} min</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-gray-800 rounded-2xl px-5 py-8 text-center mb-6">
        <p class="text-gray-400 text-sm">No patients currently waiting.</p>
    </div>
    @endif

    {{-- Auto-refresh notice --}}
    <p class="text-center text-xs text-gray-600">
        Auto-refreshes every 30s · Last updated: <span x-text="lastUpdate"></span>
    </p>

</div>

<script>
// Hard refresh every 60s to keep data fresh
setTimeout(() => location.reload(), 60000);
</script>
</body>
</html>
