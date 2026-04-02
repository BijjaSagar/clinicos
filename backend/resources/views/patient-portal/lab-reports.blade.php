<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Lab Reports</title>
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
        <h1 class="text-base font-bold text-gray-900">Lab Reports</h1>
    </div>
</header>
<main class="max-w-3xl mx-auto px-4 py-5">
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
        <div class="divide-y divide-gray-50">
            @forelse($orders as $order)
            <div class="px-5 py-4 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $order->test_name ?? 'Lab Test' }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}
                        @if($order->status === 'resulted')
                        · <span class="text-green-600 font-medium">Result ready</span>
                        @else
                        · <span class="text-amber-600 font-medium">{{ ucfirst($order->status) }}</span>
                        @endif
                    </p>
                </div>
                @if($order->status === 'resulted')
                <a href="{{ route('patient-portal.lab-report-pdf', $order->id) }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 bg-green-50 text-green-700 text-xs font-semibold rounded-lg hover:bg-green-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download PDF
                </a>
                @else
                <span class="text-xs text-gray-400 px-3 py-1.5">Pending</span>
                @endif
            </div>
            @empty
            <div class="px-5 py-10 text-center text-gray-400 text-sm">No lab reports found.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</main>
</body>
</html>
