@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
@php
/**
 * Demo data fallback — used when no controller data is passed.
 * In production the controller injects $stats, $appointments, $queue, $invoices, $whatsapp.
 */
$demoAppointments = [
    ['time'=>'09:00','name'=>'Meera Kapoor',    'initials'=>'MK','gradient'=>'#d1d5db,#9ca3af', 'type'=>'Initial Consultation','status'=>'no-show'],
    ['time'=>'09:30','name'=>'Suresh Deshpande','initials'=>'SD','gradient'=>'#6366f1,#8b5cf6', 'type'=>'Acne Review','status'=>'done'],
    ['time'=>'10:30','name'=>'Priya Mehta',     'initials'=>'PM','gradient'=>'#f59e0b,#ef4444', 'type'=>'Follow-up #4 · ABHA linked','status'=>'in-consultation'],
    ['time'=>'10:50','name'=>'Rajesh Kumar',    'initials'=>'RK','gradient'=>'#0891b2,#6366f1', 'type'=>'LASER Session #2','status'=>'waiting','token'=>7],
    ['time'=>'11:15','name'=>'Ananya Patil',    'initials'=>'AP','gradient'=>'#8b5cf6,#ec4899', 'type'=>'New Patient · Psoriasis','status'=>'confirmed'],
    ['time'=>'11:40','name'=>'Vikram Shah',     'initials'=>'VS','gradient'=>'#059669,#0891b2', 'type'=>'PRP Session · Hair Loss','status'=>'confirmed'],
    ['time'=>'12:00','name'=>'Neha Joshi',      'initials'=>'NJ','gradient'=>'#f97316,#fbbf24', 'type'=>'Chemical Peel Follow-up','status'=>'waiting','token'=>8],
    ['time'=>'12:30','name'=>'Arun Nair',       'initials'=>'AN','gradient'=>'#1447e6,#0891b2', 'type'=>'Dermatoscopy · Mole Check','status'=>'booked'],
];
$demoInvoices = [
    ['name'=>'Suresh Deshpande','initials'=>'SD','gradient'=>'#6366f1,#8b5cf6','desc'=>'Consultation + Topical Rx','amount'=>'1,800','status'=>'paid','method'=>'UPI'],
    ['name'=>'Priya Mehta',     'initials'=>'PM','gradient'=>'#f59e0b,#ef4444','desc'=>'Chem Peel + Consultation',  'amount'=>'4,200','status'=>'paid','method'=>'Card'],
    ['name'=>'Rajesh Kumar',    'initials'=>'RK','gradient'=>'#0891b2,#059669','desc'=>'LASER Session #2',          'amount'=>'5,500','status'=>'due','method'=>'Link sent'],
    ['name'=>'Ananya Patil',    'initials'=>'AP','gradient'=>'#8b5cf6,#ec4899','desc'=>'New Patient + Assessment',  'amount'=>'2,200','status'=>'advance','method'=>'Advance: ₹500'],
    ['name'=>'Vikram Shah',     'initials'=>'VS','gradient'=>'#059669,#0891b2','desc'=>'PRP Session #1',            'amount'=>'8,000','status'=>'paid','method'=>'UPI'],
];
$demoQueue = [
    ['num'=>7,'name'=>'Rajesh Kumar','type'=>'LASER Session',   'wait'=>'~12 min'],
    ['num'=>8,'name'=>'Neha Joshi',  'type'=>'Chemical Peel',   'wait'=>'~28 min'],
    ['num'=>9,'name'=>'Ananya Patil','type'=>'Not arrived yet',  'wait'=>'11:15','dim'=>true],
];
$demoWhatsapp = [
    ['name'=>'Ananya Patil',     'msg'=>'Your appointment is at 11:15 AM today...','time'=>'09:15','status'=>'delivered'],
    ['name'=>'Suresh Deshpande', 'msg'=>'e-Prescription + AI consultation summary sent','time'=>'09:51','status'=>'delivered'],
    ['name'=>'Meera Kapoor',     'msg'=>'"Can I reschedule to tomorrow morning?"','time'=>'10:02','status'=>'unread'],
];
@endphp

<div class="p-7 space-y-5">

    {{-- ── ABDM Compliance Banner ── --}}
    <div class="flex items-center gap-4 rounded-xl px-5 py-4"
         style="background:linear-gradient(135deg,#0d1117 0%,#0d1f3c 100%);">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background:rgba(20,71,230,.2);border:1px solid rgba(20,71,230,.3);">
            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <div>
            <h4 class="text-white font-semibold text-sm">ABDM Compliance Active</h4>
            <p class="text-xs mt-0.5" style="color:#64748b;">
                ABHA creation live · HFR registered · FHIR R4 records syncing ·
                {{ $stats['abdm_records'] ?? 38 }} records shared this month
            </p>
        </div>
        <div class="ml-auto flex items-center gap-2 flex-shrink-0">
            <span class="px-3 py-1 rounded-full text-xs font-semibold" style="background:rgba(5,150,105,.15);color:#6ee7b7;">M1 ✓ Live</span>
            <span class="px-3 py-1 rounded-full text-xs font-semibold" style="background:rgba(5,150,105,.15);color:#6ee7b7;">HFR ✓</span>
            <span class="px-3 py-1 rounded-full text-xs font-semibold" style="background:#1e2535;color:#94a3b8;">M2 In Progress</span>
        </div>
    </div>

    {{-- ── KPI STAT CARDS ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Today's Patients --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <p class="text-xs font-medium text-gray-400 mb-2">Today's Patients</p>
            <p class="font-display font-extrabold text-3xl text-gray-900 leading-none">{{ $stats['today_patients'] ?? 24 }}</p>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background:#ecfdf5;color:#059669;">+3</span>
                <span class="text-xs text-gray-400">vs yesterday</span>
            </div>
        </div>
        {{-- Revenue --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <p class="text-xs font-medium text-gray-400 mb-2">Today's Revenue</p>
            <p class="font-display font-extrabold text-3xl text-gray-900 leading-none">₹{{ $stats['revenue'] ?? '18,450' }}</p>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background:#ecfdf5;color:#059669;">+12%</span>
                <span class="text-xs text-gray-400">vs last week</span>
            </div>
        </div>
        {{-- Pending Dues --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <p class="text-xs font-medium text-gray-400 mb-2">Pending Collections</p>
            <p class="font-display font-extrabold text-3xl text-gray-900 leading-none">₹{{ $stats['pending_dues'] ?? '6,200' }}</p>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">3 invoices</span>
                <span class="text-xs text-gray-400">outstanding</span>
            </div>
        </div>
        {{-- Queue --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <p class="text-xs font-medium text-gray-400 mb-2">Queue Now</p>
            <p class="font-display font-extrabold text-3xl text-gray-900 leading-none">{{ $stats['queue_count'] ?? 7 }}</p>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background:#fff7ed;color:#d97706;">4 waiting</span>
                <span class="text-xs text-gray-400">in clinic</span>
            </div>
        </div>
    </div>

    {{-- ── SCHEDULE + QUEUE/WHATSAPP ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Today's Schedule --}}
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl overflow-hidden" x-data="{ filter: 'all' }">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                <h3 class="text-sm font-bold text-gray-900">Today's Schedule</h3>
                <div class="flex items-center gap-3 ml-auto">
                    <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
                        <button @click="filter='all'"
                                :class="filter==='all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                                class="px-2.5 py-1 rounded-md text-xs font-semibold transition-all">All</button>
                        <button @click="filter='waiting'"
                                :class="filter==='waiting' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                                class="px-2.5 py-1 rounded-md text-xs font-semibold transition-all">Waiting</button>
                        <button @click="filter='done'"
                                :class="filter==='done' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                                class="px-2.5 py-1 rounded-md text-xs font-semibold transition-all">Done</button>
                    </div>
                    <a href="{{ route('appointments.index') }}" class="text-xs font-semibold" style="color:#1447E6;">Full calendar →</a>
                </div>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($appointments ?? $demoAppointments as $apt)
                @php
                    $statusMap = [
                        'in-consultation' => ['label'=>'In Consultation','bg'=>'#ecfdf5','color'=>'#059669'],
                        'waiting'         => ['label'=>'Waiting · Token '.($apt['token']??''),'bg'=>'#fffbeb','color'=>'#d97706'],
                        'confirmed'       => ['label'=>'Confirmed','bg'=>'#eff3ff','color'=>'#1447e6'],
                        'done'            => ['label'=>'Done','bg'=>'#f1f5f9','color'=>'#9ca3af'],
                        'no-show'         => ['label'=>'No-show','bg'=>'#fff1f2','color'=>'#dc2626'],
                        'booked'          => ['label'=>'Booked','bg'=>'#f0f9ff','color'=>'#0891b2'],
                    ];
                    $s = $statusMap[$apt['status']] ?? $statusMap['booked'];
                    $opacity = in_array($apt['status'], ['done','no-show']) ? 'opacity-60' : '';
                @endphp
                <div class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors cursor-pointer {{ $opacity }}
                            {{ $apt['status']==='in-consultation' ? 'bg-green-50/50' : '' }}">
                    <span class="text-xs font-semibold text-gray-400 w-12 flex-shrink-0 text-right">{{ $apt['time'] }}</span>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                         style="background:linear-gradient(135deg,{{ $apt['gradient'] ?? '#1447e6,#0891b2' }})">
                        {{ $apt['initials'] }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $apt['name'] }}</p>
                        <p class="text-xs text-gray-400 truncate mt-0.5">{{ $apt['type'] }}</p>
                    </div>
                    <span class="flex-shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full"
                          style="background:{{ $s['bg'] }};color:{{ $s['color'] }};">{{ $s['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Queue + WhatsApp --}}
        <div class="flex flex-col gap-4">

            {{-- Live Queue --}}
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900">Live Queue</h3>
                    <a href="#" class="text-xs font-semibold" style="color:#1447E6;">Manage →</a>
                </div>
                <div class="p-4">
                    {{-- Now Serving --}}
                    <div class="rounded-xl p-4 text-center mb-3"
                         style="background:linear-gradient(135deg,#1447E6 0%,#0891B2 100%);">
                        <p class="text-xs font-semibold uppercase tracking-widest mb-1" style="color:rgba(255,255,255,.7);">Now Serving</p>
                        <p class="font-display font-extrabold text-5xl text-white leading-none">{{ $stats['current_token'] ?? 6 }}</p>
                        <p class="text-xs mt-1.5" style="color:rgba(255,255,255,.75);">{{ $stats['current_patient'] ?? 'Priya Mehta' }} · Est. 8 mins</p>
                    </div>
                    {{-- Queue List --}}
                    <div class="space-y-1.5">
                        @foreach($queue ?? $demoQueue as $q)
                        <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-gray-50 {{ ($q['dim']??false) ? 'opacity-50' : '' }}">
                            <div class="w-7 h-7 rounded-md bg-white border border-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 flex-shrink-0">
                                {{ $q['num'] }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-900 truncate">{{ $q['name'] }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $q['type'] }}</p>
                            </div>
                            <span class="text-xs text-gray-400 flex-shrink-0">{{ $q['wait'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- WhatsApp Activity --}}
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900">WhatsApp Activity</h3>
                    <a href="#" class="text-xs font-semibold" style="color:#1447E6;">View all →</a>
                </div>
                <div class="px-4 py-3 space-y-2">
                    @foreach($whatsapp ?? $demoWhatsapp as $wa)
                    <div class="flex items-start gap-2.5 px-3 py-2.5 rounded-lg {{ $wa['status']==='unread' ? 'bg-orange-50' : 'bg-gray-50' }}">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs flex-shrink-0"
                             style="background:#25D366;">💬</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-900 truncate">
                                {{ $wa['status']==='unread' ? 'Reply — ' : 'Sent — ' }}{{ $wa['name'] }}
                            </p>
                            <p class="text-xs text-gray-400 truncate mt-0.5">{{ $wa['msg'] }}</p>
                            @if($wa['status']==='unread')
                                <p class="text-xs font-semibold mt-0.5" style="color:#d97706;">● Needs reply</p>
                            @else
                                <p class="text-xs font-semibold mt-0.5" style="color:#25D366;">✓✓ Delivered</p>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0">{{ $wa['time'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    {{-- ── REVENUE CHART + RECENT INVOICES + AI PANEL ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Revenue Bar Chart --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900">Revenue — This Week</h3>
                <span class="font-display font-extrabold text-lg text-gray-900">₹1.04L</span>
            </div>
            <div class="p-5">
                <div style="height:140px;position:relative;">
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="flex gap-4 mt-4 pt-4 border-t border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400">Collected</p>
                        <p class="text-sm font-bold" style="color:#059669;">₹94,800</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Pending</p>
                        <p class="text-sm font-bold" style="color:#d97706;">₹9,200</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">GST</p>
                        <p class="text-sm font-bold text-gray-600">₹3,240</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Invoices --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900">Recent Invoices</h3>
                <a href="#" class="text-xs font-semibold" style="color:#1447E6;">All invoices →</a>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($invoices ?? $demoInvoices as $inv)
                @php
                    $isPaid = $inv['status']==='paid';
                    $isDue  = $inv['status']==='due';
                @endphp
                <div class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                         style="background:linear-gradient(135deg,{{ $inv['gradient'] }})">
                        {{ $inv['initials'] }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-900 truncate">{{ $inv['name'] }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $inv['desc'] }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-bold text-gray-900">₹{{ $inv['amount'] }}</p>
                        <p class="text-xs font-semibold mt-0.5 {{ $isPaid ? 'text-green-600' : ($isDue ? 'text-amber-600' : 'text-blue-600') }}">
                            {{ $isPaid ? 'Paid · '.$inv['method'] : ($isDue ? 'Due · '.$inv['method'] : $inv['method']) }}
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- AI Suggestions + Visits by Type --}}
        <div class="flex flex-col gap-4">

            {{-- AI Panel --}}
            <div class="rounded-xl p-4" style="background:linear-gradient(135deg,rgba(20,71,230,.04),rgba(8,145,178,.04));border:1px solid rgba(20,71,230,.12);">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold" style="background:#1447E6;">✦</div>
                    <h4 class="text-sm font-bold text-gray-900">AI Suggestions</h4>
                    <button class="ml-auto text-xs font-semibold" style="color:#1447E6;">Dismiss all</button>
                </div>
                @php
                    $aiSuggestions = $suggestions ?? [
                        ['title'=>'📋 Start Priya Mehta\'s note','body'=>'Patient is in consultation now. Tap to open Dermatology EMR with last visit pre-filled.'],
                        ['title'=>'💊 Prescription template ready','body'=>'Acne Grade 3 — suggested: Adapalene 0.1% + Clindamycin 1%. Review and send.'],
                        ['title'=>'📆 Recall due — 6 patients','body'=>'Psoriasis patients due for 6-week review. Send WhatsApp recall batch?'],
                    ];
                @endphp
                @foreach($aiSuggestions as $sug)
                <div class="bg-white rounded-lg border border-gray-200 p-3 mb-2 cursor-pointer hover:border-blue-300 transition-colors">
                    <p class="text-xs font-semibold text-gray-900 mb-1">{{ $sug['title'] }}</p>
                    <p class="text-xs text-gray-400 leading-relaxed">{{ $sug['body'] }}</p>
                </div>
                @endforeach
            </div>

            {{-- Visits by Type --}}
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900">Visits by Type</h3>
                </div>
                <div class="p-5 space-y-3">
                    @php
                        $visitTypes = [
                            ['label'=>'Consultation','pct'=>65,'color'=>'#1447E6'],
                            ['label'=>'LASER',       'pct'=>18,'color'=>'#0891B2'],
                            ['label'=>'PRP',         'pct'=>10,'color'=>'#8b5cf6'],
                            ['label'=>'Chem Peel',   'pct'=>7, 'color'=>'#d97706'],
                        ];
                    @endphp
                    @foreach($visitTypes as $vt)
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-600 w-20 flex-shrink-0">{{ $vt['label'] }}</span>
                        <div class="flex-1 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full" style="width:{{ $vt['pct'] }}%;background:{{ $vt['color'] }};"></div>
                        </div>
                        <span class="text-xs font-semibold text-gray-700 w-8 text-right">{{ $vt['pct'] }}%</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>{{-- /page --}}

{{-- ── FAB: New Patient ── --}}
<a href="{{ route('patients.create') }}"
   class="fixed bottom-8 right-8 flex items-center gap-2 text-white font-semibold text-sm px-5 py-3 rounded-full shadow-xl hover:shadow-2xl transition-all hover:scale-105 z-50"
   style="background:linear-gradient(135deg,#1447E6,#0891B2);">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
    New Patient
</a>

@endsection

@push('scripts')
<script>
(function() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            datasets: [{
                data: [18000, 27500, 22000, 32500, 0, 0],
                backgroundColor: ['#bfdbfe','#93c5fd','#93c5fd','#1447E6','#e5e7eb','#e5e7eb'],
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: {
                callbacks: { label: ctx => '₹' + ctx.raw.toLocaleString('en-IN') }
            }},
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af' } },
                y: { display: false, grid: { display: false } }
            }
        }
    });
})();
</script>
@endpush
