<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a visit — Find a clinic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%); min-height: 100vh; }
        .sora { font-family: 'Sora', sans-serif; }
    </style>
</head>
<body class="antialiased text-gray-900">
    <div class="min-h-screen py-10 px-4">
        <header class="max-w-3xl mx-auto text-center mb-10">
            <p class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-2">Patient booking</p>
            <h1 class="sora text-3xl sm:text-4xl font-bold text-gray-900">Book an appointment</h1>
            <p class="text-gray-600 mt-3 text-sm sm:text-base max-w-xl mx-auto">
                Choose your clinic below. You’ll see the full doctor list and available times — no account needed.
                This page is served at <code class="bg-white/80 px-1.5 py-0.5 rounded text-xs font-mono text-blue-800">/book</code> (Laravel route), not as a file inside the <code class="text-xs font-mono">public/</code> folder.
            </p>
            <a href="{{ url('/') }}" class="inline-block mt-4 text-sm text-gray-500 hover:text-blue-600">← Back to ClinicOS home</a>
        </header>

        <div class="max-w-3xl mx-auto space-y-4">
            @forelse($clinics as $clinic)
            <article class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-7 hover:border-blue-300 hover:shadow-md transition-all">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="flex gap-4 min-w-0">
                        @if($clinic->logo_url)
                            <img src="{{ $clinic->logo_url }}" alt="" class="w-14 h-14 rounded-xl object-cover border border-gray-100 flex-shrink-0">
                        @else
                            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
                                {{ strtoupper(substr($clinic->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h2 class="sora text-lg font-bold text-gray-900">{{ $clinic->name }}</h2>
                            <p class="text-sm text-gray-500 mt-1">
                                @if($clinic->city)
                                    {{ $clinic->city }}{{ $clinic->state ? ', '.$clinic->state : '' }}
                                @elseif($clinic->address_line1)
                                    {{ $clinic->address_line1 }}
                                @else
                                    Location on file
                                @endif
                            </p>
                            @if($clinic->phone)
                                <p class="text-sm text-gray-600 mt-1">📞 {{ $clinic->phone }}</p>
                            @endif
                            @if(is_array($clinic->specialties) && count($clinic->specialties))
                                <p class="text-xs text-gray-500 mt-2">
                                    {{ collect($clinic->specialties)->take(4)->implode(' · ') }}
                                </p>
                            @endif
                            <p class="text-xs text-teal-700 font-medium mt-2">
                                {{ (int) ($clinic->public_doctors_count ?? 0) }} doctor{{ ((int)($clinic->public_doctors_count ?? 0)) === 1 ? '' : 's' }} available to book
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:items-end gap-2 flex-shrink-0">
                        <a href="{{ route('public.booking.show', ['clinicSlug' => $clinic->slug]) }}"
                           class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-colors shadow-sm">
                            View doctors &amp; book
                        </a>
                        <span class="text-[10px] text-gray-400 font-mono sm:text-right">/book/{{ $clinic->slug }}</span>
                    </div>
                </div>
            </article>
            @empty
            <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-12 text-center">
                <p class="text-gray-600 font-medium">No clinics are open for online booking yet.</p>
                <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">
                    Each clinic needs an active profile, a unique <strong>slug</strong>, and online booking enabled in settings.
                    Ask your clinic administrator to set this up in ClinicOS.
                </p>
            </div>
            @endforelse
        </div>

        <footer class="max-w-3xl mx-auto mt-12 text-center text-xs text-gray-400">
            <p>Powered by <span class="font-semibold text-gray-500">ClinicOS</span></p>
        </footer>
    </div>
    <script>console.log('[patient-hub] /book directory loaded, clinics: {{ $clinics->count() }}');</script>
</body>
</html>
