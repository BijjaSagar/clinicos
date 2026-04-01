@extends('layouts.app')

@section('title', 'Schedule')
@section('breadcrumb', 'Schedule')

@push('styles')
<style>
    :root {
        --slot-height: 60px;
    }
    .schedule-container {
        display: flex;
        gap: 20px;
        height: calc(100vh - 200px);
    }
    .schedule-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .schedule-sidebar {
        width: 320px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    /* Calendar Header */
    .calendar-header {
        background: white;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        border: 1px solid #e5e7eb;
        margin-bottom: 16px;
    }
    .date-nav {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .date-nav-btn {
        width: 32px;
        height: 32px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        background: white;
        color: #6b7280;
        transition: all 0.15s;
    }
    .date-nav-btn:hover {
        border-color: #1447e6;
        color: #1447e6;
    }
    .current-date {
        font-size: 18px;
        font-weight: 700;
        color: #0d1117;
        min-width: 200px;
    }
    .view-toggle {
        display: flex;
        background: #f3f4f6;
        border-radius: 8px;
        padding: 3px;
    }
    .view-btn {
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        background: transparent;
        color: #6b7280;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.15s;
    }
    .view-btn.active {
        background: white;
        color: #0d1117;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* Resource Filter */
    .resource-filter {
        display: flex;
        gap: 8px;
        margin-left: auto;
    }
    .resource-chip {
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 600;
        border: 1.5px solid #e5e7eb;
        border-radius: 100px;
        cursor: pointer;
        background: white;
        transition: all 0.15s;
    }
    .resource-chip:hover {
        border-color: #1447e6;
    }
    .resource-chip.active {
        background: #1447e6;
        border-color: #1447e6;
        color: white;
    }
    
    /* Timeline Grid */
    .timeline-grid {
        flex: 1;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: auto;
        position: relative;
    }
    .timeline-header {
        display: grid;
        grid-template-columns: 70px repeat(auto-fill, minmax(180px, 1fr));
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .timeline-header-cell {
        padding: 12px;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-align: center;
        border-right: 1px solid #e5e7eb;
    }
    .timeline-header-cell:last-child {
        border-right: none;
    }
    .resource-name {
        font-size: 13px;
        font-weight: 700;
        color: #0d1117;
    }
    .resource-type {
        font-size: 10px;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .timeline-body {
        display: grid;
        grid-template-columns: 70px repeat(auto-fill, minmax(180px, 1fr));
    }
    .time-column {
        border-right: 1px solid #e5e7eb;
    }
    .time-slot {
        height: var(--slot-height);
        padding: 4px 8px;
        font-size: 11px;
        color: #9ca3af;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: flex-start;
    }
    .time-slot:nth-child(2n) {
        background: #fafafa;
    }
    
    .resource-column {
        border-right: 1px solid #e5e7eb;
        position: relative;
    }
    .resource-column:last-child {
        border-right: none;
    }
    .slot-row {
        height: var(--slot-height);
        border-bottom: 1px solid #f3f4f6;
        position: relative;
    }
    .slot-row:nth-child(2n) {
        background: #fafafa;
    }
    .slot-row:hover {
        background: rgba(20, 71, 230, 0.03);
    }
    
    /* Appointment Cards */
    .appointment-card {
        position: absolute;
        left: 4px;
        right: 4px;
        border-radius: 6px;
        padding: 6px 8px;
        font-size: 11px;
        cursor: pointer;
        overflow: hidden;
        transition: box-shadow 0.15s, transform 0.15s;
        z-index: 5;
    }
    .appointment-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-1px);
        z-index: 6;
    }
    .appointment-card.status-booked,
    .appointment-card.status-confirmed {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border: 1px solid #93c5fd;
        color: #1e40af;
    }
    .appointment-card.status-checked_in {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        border: 1px solid #6ee7b7;
        color: #065f46;
    }
    .appointment-card.status-in_consultation {
        background: linear-gradient(135deg, #fefce8, #fef08a);
        border: 1px solid #fcd34d;
        color: #92400e;
    }
    .appointment-card.status-completed {
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        color: #6b7280;
    }
    .appointment-card.status-cancelled,
    .appointment-card.status-no_show {
        background: #fef2f2;
        border: 1px solid #fca5a5;
        color: #991b1b;
        text-decoration: line-through;
        opacity: 0.7;
    }
    
    .apt-time {
        font-weight: 700;
        font-size: 10px;
    }
    .apt-patient {
        font-weight: 600;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .apt-type {
        font-size: 10px;
        opacity: 0.8;
    }
    
    /* Sidebar Cards */
    .sidebar-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
    }
    .sidebar-card h3 {
        font-size: 13px;
        font-weight: 700;
        color: #0d1117;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Stats */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    .stat-item {
        padding: 10px;
        background: #f9fafb;
        border-radius: 8px;
        text-align: center;
    }
    .stat-value {
        font-size: 20px;
        font-weight: 700;
        color: #0d1117;
    }
    .stat-label {
        font-size: 10px;
        color: #6b7280;
        margin-top: 2px;
    }
    
    /* Queue List */
    .queue-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 200px;
        overflow-y: auto;
    }
    .queue-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: #f9fafb;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .queue-item:hover {
        background: #eff6ff;
    }
    .queue-token {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 700;
        color: white;
    }
    .queue-token.checked-in { background: #22c55e; }
    .queue-token.waiting { background: #f59e0b; }
    .queue-info {
        flex: 1;
        min-width: 0;
    }
    .queue-name {
        font-size: 12px;
        font-weight: 600;
        color: #0d1117;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .queue-time {
        font-size: 10px;
        color: #6b7280;
    }
    .queue-action {
        padding: 4px 10px;
        font-size: 10px;
        font-weight: 600;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        background: #1447e6;
        color: white;
    }
    
    /* Room Status */
    .room-grid {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .room-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        background: #f9fafb;
        border-radius: 8px;
    }
    .room-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }
    .room-indicator.available { background: #22c55e; }
    .room-indicator.occupied { background: #ef4444; }
    .room-indicator.cleaning { background: #f59e0b; }
    .room-name {
        font-size: 12px;
        font-weight: 500;
        color: #0d1117;
        flex: 1;
    }
    .room-status {
        font-size: 10px;
        font-weight: 500;
        padding: 2px 8px;
        border-radius: 100px;
    }
    .room-status.available { background: #dcfce7; color: #166534; }
    .room-status.occupied { background: #fee2e2; color: #991b1b; }
    .room-status.cleaning { background: #fef3c7; color: #92400e; }
    
    /* Empty State */
    .empty-slot {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d1d5db;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .empty-slot:hover {
        color: #1447e6;
        background: rgba(20, 71, 230, 0.05);
    }
    
    /* Quick Add Button */
    .quick-add-btn {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1447e6, #0891b2);
        color: white;
        border: none;
        font-size: 24px;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(20, 71, 230, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.15s, box-shadow 0.15s;
        z-index: 20;
    }
    .quick-add-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 24px rgba(20, 71, 230, 0.5);
    }
</style>
@endpush

@section('content')
@php
    $waitEstimates = $waitEstimates ?? [];
    $today = request('date') ? \Carbon\Carbon::parse(request('date')) : today();
    $doctors = \App\Models\User::where('clinic_id', auth()->user()->clinic_id)
        ->whereIn('role', ['doctor', 'owner'])
        ->where('is_active', true)
        ->get();
    $rooms = \DB::table('clinic_rooms')
        ->where('clinic_id', auth()->user()->clinic_id)
        ->where('is_active', true)
        ->get();
    $equipment = \DB::table('clinic_equipment')
        ->where('clinic_id', auth()->user()->clinic_id)
        ->where('is_active', true)
        ->get();
    
    // Organize appointments by time slot
    $appointmentsByTime = $appointments->groupBy(function($apt) {
        return \Carbon\Carbon::parse($apt->scheduled_at)->format('H:i');
    });
    
    // Get queue (checked-in patients)
    $queue = $appointments->whereIn('status', ['checked_in', 'in_consultation'])->sortBy('scheduled_at');
    
    // Stats
    $stats = [
        'total' => $appointments->count(),
        'completed' => $appointments->where('status', 'completed')->count(),
        'in_queue' => $queue->count(),
        'cancelled' => $appointments->whereIn('status', ['cancelled', 'no_show'])->count(),
    ];
    
    // Align with public booking + clinic settings (slot_duration_mins); fallback 30-min grid
    $timeSlots = $timeSlots ?? [];
    if (count($timeSlots) === 0) {
        $timeSlots = [];
        for ($h = 8; $h <= 20; $h++) {
            $timeSlots[] = sprintf('%02d:00', $h);
            $timeSlots[] = sprintf('%02d:30', $h);
        }
    }
@endphp

<div class="schedule-container" x-data="{ view: 'day', showQuickAdd: false }">
    {{-- Main Schedule Area --}}
    <div class="schedule-main">
        {{-- Calendar Header --}}
        <div class="calendar-header">
            <div class="date-nav">
                <a href="{{ route('schedule', ['date' => $today->copy()->subDay()->toDateString()]) }}" class="date-nav-btn">
                    <svg style="width:16px;height:16px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </a>
                <a href="{{ route('schedule', ['date' => today()->toDateString()]) }}" class="date-nav-btn" style="font-size:11px;width:auto;padding:0 10px">Today</a>
                <a href="{{ route('schedule', ['date' => $today->copy()->addDay()->toDateString()]) }}" class="date-nav-btn">
                    <svg style="width:16px;height:16px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
            </div>
            
            <div class="current-date">
                @if($today->isToday())
                    Today, {{ $today->format('d M Y') }}
                @else
                    {{ $today->format('l, d M Y') }}
                @endif
            </div>
            
            <div class="view-toggle">
                <button class="view-btn" :class="{ 'active': view === 'day' }" @click="view = 'day'">Day</button>
                <button class="view-btn" :class="{ 'active': view === 'week' }" @click="view = 'week'">Week</button>
                <button class="view-btn" :class="{ 'active': view === 'list' }" @click="view = 'list'">List</button>
            </div>
            
            <div class="resource-filter">
                @foreach($doctors->take(4) as $doc)
                <span class="resource-chip active" style="background:{{ ['#1447e6','#059669','#d97706','#dc2626'][$loop->index % 4] }};border-color:{{ ['#1447e6','#059669','#d97706','#dc2626'][$loop->index % 4] }};color:white">
                    {{ Str::limit($doc->name, 8) }}
                </span>
                @endforeach
            </div>
        </div>
        
        {{-- Timeline Grid --}}
        <div class="timeline-grid">
            {{-- Header Row --}}
            <div class="timeline-header">
                <div class="timeline-header-cell">Time</div>
                @foreach($doctors as $doctor)
                <div class="timeline-header-cell">
                    <div class="resource-name">Dr. {{ $doctor->name }}</div>
                    <div class="resource-type">{{ $doctor->specialty ?? 'General' }}</div>
                </div>
                @endforeach
                @if($rooms->isEmpty() && $doctors->isEmpty())
                <div class="timeline-header-cell">
                    <div class="resource-name">All Appointments</div>
                </div>
                @endif
            </div>
            
            {{-- Time Rows --}}
            <div class="timeline-body">
                {{-- Time Column --}}
                <div class="time-column">
                    @foreach($timeSlots as $slot)
                    <div class="time-slot">{{ \Carbon\Carbon::parse($slot)->format('h:i A') }}</div>
                    @endforeach
                </div>
                
                {{-- Resource Columns --}}
                @forelse($doctors as $doctor)
                <div class="resource-column">
                    @foreach($timeSlots as $slotIndex => $slot)
                    @php
                        $slotAppts = $appointments->filter(function($apt) use ($slot, $doctor) {
                            $aptTime = \Carbon\Carbon::parse($apt->scheduled_at)->format('H:i');
                            return $aptTime === $slot && (int) $apt->doctor_id === (int) $doctor->id;
                        });
                    @endphp
                    <div class="slot-row">
                        @foreach($slotAppts as $apt)
                        @php
                            $duration = $apt->duration_mins ?? 30;
                            $heightMultiplier = $duration / 30;
                        @endphp
                        <a href="{{ route('appointments.show', $apt) }}" 
                           class="appointment-card status-{{ $apt->status }}"
                           style="height: calc(var(--slot-height) * {{ $heightMultiplier }} - 4px);">
                            <div class="apt-time">{{ \Carbon\Carbon::parse($apt->scheduled_at)->format('h:i A') }}</div>
                            <div class="apt-patient">{{ $apt->patient->name ?? 'Unknown' }}</div>
                            <div class="apt-type">{{ ucfirst($apt->appointment_type ?? 'Consultation') }}</div>
                            @php $w = $waitEstimates[$apt->id] ?? null; @endphp
                            @if($w && ($w['ahead'] ?? 0) > 0)
                            <div class="apt-wait" style="font-size:10px;color:#64748b;margin-top:4px;">~{{ $w['minutes'] }}m queue ({{ $w['ahead'] }} ahead)</div>
                            @endif
                        </a>
                        @endforeach
                        
                        @if($slotAppts->isEmpty())
                        <div class="empty-slot" onclick="window.location.href='{{ route('appointments.create', ['date' => $today->toDateString(), 'time' => $slot, 'doctor' => $doctor->id]) }}'">+</div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @empty
                {{-- No doctors configured - show single column --}}
                <div class="resource-column">
                    @foreach($timeSlots as $slot)
                    @php
                        $slotAppts = $appointments->filter(function($apt) use ($slot) {
                            return \Carbon\Carbon::parse($apt->scheduled_at)->format('H:i') === $slot;
                        });
                    @endphp
                    <div class="slot-row">
                        @foreach($slotAppts as $apt)
                        <a href="{{ route('appointments.show', $apt) }}" 
                           class="appointment-card status-{{ $apt->status }}">
                            <div class="apt-time">{{ \Carbon\Carbon::parse($apt->scheduled_at)->format('h:i A') }}</div>
                            <div class="apt-patient">{{ $apt->patient->name ?? 'Unknown' }}</div>
                            <div class="apt-type">{{ ucfirst($apt->appointment_type ?? 'Consultation') }}</div>
                            @php $w = $waitEstimates[$apt->id] ?? null; @endphp
                            @if($w && ($w['ahead'] ?? 0) > 0)
                            <div class="apt-wait" style="font-size:10px;color:#64748b;margin-top:4px;">~{{ $w['minutes'] }}m queue ({{ $w['ahead'] }} ahead)</div>
                            @endif
                        </a>
                        @endforeach
                    </div>
                    @endforeach
                </div>
                @endforelse
            </div>
        </div>
    </div>
    
    {{-- Sidebar --}}
    <div class="schedule-sidebar">
        {{-- Today's Stats --}}
        <div class="sidebar-card">
            <h3>
                <svg style="width:16px;height:16px;color:#1447e6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
                Today's Stats
            </h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">{{ $stats['total'] }}</div>
                    <div class="stat-label">Total Appts</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color:#22c55e">{{ $stats['completed'] }}</div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color:#f59e0b">{{ $stats['in_queue'] }}</div>
                    <div class="stat-label">In Queue</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color:#ef4444">{{ $stats['cancelled'] }}</div>
                    <div class="stat-label">Cancelled</div>
                </div>
            </div>
        </div>
        
        {{-- Patient Queue --}}
        <div class="sidebar-card">
            <h3>
                <svg style="width:16px;height:16px;color:#f59e0b" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                Patient Queue
                @if($queue->count() > 0)
                <span style="margin-left:auto;background:#f59e0b;color:white;padding:2px 8px;border-radius:100px;font-size:10px">{{ $queue->count() }}</span>
                @endif
            </h3>
            
            <div class="queue-list">
                @forelse($queue as $apt)
                <div class="queue-item">
                    <div class="queue-token {{ $apt->status === 'checked_in' ? 'waiting' : 'checked-in' }}">
                        {{ $apt->token_number ?? $loop->iteration }}
                    </div>
                    <div class="queue-info">
                        <div class="queue-name">{{ $apt->patient->name ?? 'Unknown' }}</div>
                        <div class="queue-time">
                            {{ \Carbon\Carbon::parse($apt->scheduled_at)->format('h:i A') }}
                            @if($apt->status === 'in_consultation')
                                · In consultation
                            @else
                                · Waiting {{ \Carbon\Carbon::parse($apt->scheduled_at)->diffForHumans() }}
                            @endif
                        </div>
                    </div>
                    @if($apt->status === 'checked_in')
                    <form action="{{ route('appointments.status', $apt) }}" method="POST" style="margin:0">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="in_consultation">
                        <button type="submit" class="queue-action">Start</button>
                    </form>
                    @endif
                </div>
                @empty
                <div style="padding:16px;text-align:center;color:#9ca3af;font-size:12px">
                    No patients in queue
                </div>
                @endforelse
            </div>
        </div>
        
        {{-- Room Status --}}
        @if($rooms->isNotEmpty())
        <div class="sidebar-card">
            <h3>
                <svg style="width:16px;height:16px;color:#059669" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
                </svg>
                Room Status
            </h3>
            <div class="room-grid">
                @foreach($rooms as $room)
                @php
                    // Check if room is currently occupied
                    $currentApt = $appointments->first(function($apt) use ($room) {
                        return $apt->room_id == $room->id && $apt->status === 'in_consultation';
                    });
                    $status = $currentApt ? 'occupied' : 'available';
                @endphp
                <div class="room-item">
                    <div class="room-indicator {{ $status }}"></div>
                    <div class="room-name">{{ $room->name }}</div>
                    <span class="room-status {{ $status }}">{{ ucfirst($status) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        
        {{-- Equipment Status --}}
        @if($equipment->isNotEmpty())
        <div class="sidebar-card">
            <h3>
                <svg style="width:16px;height:16px;color:#8b5cf6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
                </svg>
                Equipment
            </h3>
            <div class="room-grid">
                @foreach($equipment->take(5) as $equip)
                <div class="room-item">
                    <div class="room-indicator available"></div>
                    <div class="room-name">{{ $equip->name }}</div>
                    <span class="room-status available">Available</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Quick Add Button --}}
<a href="{{ route('appointments.create') }}" class="quick-add-btn" title="New Appointment">
    +
</a>

@if(session('success'))
<script>console.log('Success: {{ session('success') }}');</script>
@endif

@if(session('error'))
<script>console.log('Error: {{ session('error') }}');</script>
@endif
@endsection

@push('scripts')
<script>
console.log('Schedule page loaded');
console.log('Appointments:', {{ $appointments->count() }});
console.log('Queue:', {{ $queue->count() }});
</script>
@endpush
