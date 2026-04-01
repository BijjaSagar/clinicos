@extends('layouts.app')

@section('title', 'Appointment Details')
@section('breadcrumb', 'Schedule / Appointment #' . $appointment->id)

@push('styles')
<style>
    .appointment-container {
        max-width: 900px;
        margin: 0 auto;
    }
    .appointment-header {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 20px;
    }
    .patient-avatar {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .header-info {
        flex: 1;
    }
    .header-info h1 {
        font-size: 22px;
        font-weight: 700;
        color: #0d1117;
        margin: 0;
    }
    .header-meta {
        display: flex;
        gap: 16px;
        margin-top: 8px;
        flex-wrap: wrap;
    }
    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #6b7280;
    }
    .meta-item svg {
        width: 16px;
        height: 16px;
    }
    .status-badge {
        padding: 6px 14px;
        border-radius: 100px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-booked, .status-confirmed {
        background: #dbeafe;
        color: #1e40af;
    }
    .status-checked_in {
        background: #dcfce7;
        color: #166534;
    }
    .status-in_consultation {
        background: #fef3c7;
        color: #92400e;
    }
    .status-completed {
        background: #f3f4f6;
        color: #6b7280;
    }
    .status-cancelled, .status-no_show {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .header-actions {
        display: flex;
        gap: 8px;
    }
    .action-btn {
        padding: 10px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s;
        text-decoration: none;
    }
    .action-btn-primary {
        background: linear-gradient(135deg, #1447e6, #0891b2);
        color: white;
        border: none;
    }
    .action-btn-primary:hover {
        box-shadow: 0 4px 12px rgba(20, 71, 230, 0.4);
    }
    .action-btn-secondary {
        background: white;
        color: #6b7280;
        border: 1.5px solid #e5e7eb;
    }
    .action-btn-secondary:hover {
        border-color: #d1d5db;
        color: #0d1117;
    }
    .action-btn-success {
        background: #22c55e;
        color: white;
        border: none;
    }
    .action-btn-warning {
        background: #f59e0b;
        color: white;
        border: none;
    }
    .action-btn-danger {
        background: #ef4444;
        color: white;
        border: none;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    .detail-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 20px;
    }
    .detail-card h3 {
        font-size: 13px;
        font-weight: 700;
        color: #0d1117;
        margin: 0 0 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .detail-card h3 svg {
        width: 18px;
        height: 18px;
        color: #1447e6;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .detail-row:last-child {
        border-bottom: none;
    }
    .detail-label {
        font-size: 12px;
        color: #6b7280;
    }
    .detail-value {
        font-size: 13px;
        font-weight: 500;
        color: #0d1117;
    }
    
    .timeline {
        padding-left: 24px;
        border-left: 2px solid #e5e7eb;
        margin-left: 10px;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 16px;
    }
    .timeline-item:last-child {
        padding-bottom: 0;
    }
    .timeline-dot {
        position: absolute;
        left: -29px;
        top: 2px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #d1d5db;
    }
    .timeline-dot.active {
        background: #22c55e;
    }
    .timeline-dot.current {
        background: #f59e0b;
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.2);
    }
    .timeline-content {
        font-size: 12px;
    }
    .timeline-title {
        font-weight: 600;
        color: #0d1117;
    }
    .timeline-time {
        color: #6b7280;
        margin-top: 2px;
    }
    
    .workflow-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
    }
</style>
@endpush

@section('content')
@php
    $patient = $appointment->patient;
    $doctor = $appointment->doctor;
    $statusLabels = [
        'booked' => 'Booked',
        'confirmed' => 'Confirmed',
        'checked_in' => 'Checked In',
        'in_consultation' => 'In Consultation',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show' => 'No Show',
        'rescheduled' => 'Rescheduled',
    ];
@endphp

<div class="appointment-container">
    {{-- Header --}}
    <div class="appointment-header">
        <div class="patient-avatar">
            {{ strtoupper(substr($patient->name ?? 'P', 0, 1)) }}
        </div>
        
        <div class="header-info">
            <h1>{{ $patient->name ?? 'Unknown Patient' }}</h1>
            <div class="header-meta">
                <div class="meta-item">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                    {{ \Carbon\Carbon::parse($appointment->scheduled_at)->format('d M Y, h:i A') }}
                </div>
                <div class="meta-item">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $appointment->duration_mins ?? 30 }} minutes
                </div>
                <div class="meta-item">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    Dr. {{ $doctor->name ?? 'Unassigned' }}
                </div>
                <span class="status-badge status-{{ $appointment->status }}">
                    {{ $statusLabels[$appointment->status] ?? ucfirst($appointment->status) }}
                </span>
            </div>
        </div>
        
        <div class="header-actions">
            @if($appointment->status === 'confirmed')
            <form action="{{ route('appointments.status', $appointment) }}" method="POST" style="margin:0">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="checked_in">
                <button type="submit" class="action-btn action-btn-success">Check In</button>
            </form>
            @endif
            
            @if($appointment->status === 'checked_in')
            <form action="{{ route('appointments.status', $appointment) }}" method="POST" style="margin:0">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="in_consultation">
                <button type="submit" class="action-btn action-btn-warning">Start Consultation</button>
            </form>
            @endif
            
            @if($appointment->status === 'in_consultation')
            @if($appointment->visit)
            <a href="{{ route('emr.show', [$patient, $appointment->visit]) }}" class="action-btn action-btn-primary">
                Open EMR
            </a>
            @else
            <form action="{{ route('emr.create', $patient) }}" method="POST" style="margin:0">
                @csrf
                <button type="submit" class="action-btn action-btn-primary">Create Visit Note</button>
            </form>
            @endif
            @endif
            
            @if(!in_array($appointment->status, ['completed', 'cancelled', 'no_show']))
            <form action="{{ route('appointments.destroy', $appointment) }}" method="POST" style="margin:0" onsubmit="return confirm('Cancel this appointment?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="action-btn action-btn-danger">Cancel</button>
            </form>
            @endif
        </div>
    </div>
    
    {{-- Detail Grid --}}
    <div class="detail-grid">
        {{-- Patient Info --}}
        <div class="detail-card">
            <h3>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
                Patient Information
            </h3>
            
            <div class="detail-row">
                <span class="detail-label">Name</span>
                <span class="detail-value">{{ $patient->name ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span class="detail-value">{{ $patient->phone ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Age / Gender</span>
                <span class="detail-value">
                    {{ $patient->age_years ?? '-' }} yrs / {{ $patient->sex ?? '-' }}
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Visits</span>
                <span class="detail-value">{{ $patient->visit_count ?? 0 }}</span>
            </div>
            
            <div style="margin-top:16px">
                <a href="{{ route('patients.show', $patient) }}" class="action-btn action-btn-secondary" style="width:100%;justify-content:center">
                    View Patient Profile
                </a>
            </div>
        </div>
        
        {{-- Appointment Details --}}
        <div class="detail-card">
            <h3>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
                Appointment Details
            </h3>
            
            <div class="detail-row">
                <span class="detail-label">Type</span>
                <span class="detail-value">{{ ucfirst($appointment->appointment_type ?? 'Consultation') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Specialty</span>
                <span class="detail-value">{{ ucfirst($appointment->specialty ?? 'General') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Booking Source</span>
                <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $appointment->booking_source ?? 'clinic_staff')) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Token #</span>
                <span class="detail-value">{{ $appointment->token_number ?? '-' }}</span>
            </div>
            @if(!empty($appointment->pre_visit_token) && $appointment->clinic)
            <div class="detail-row">
                <span class="detail-label">Pre-visit link</span>
                <span class="detail-value" style="word-break:break-all;font-size:12px;">
                    <a href="{{ url('/book/' . $appointment->clinic->slug . '/pre-visit/' . $appointment->pre_visit_token) }}" target="_blank" rel="noopener">
                        {{ url('/book/' . $appointment->clinic->slug . '/pre-visit/' . $appointment->pre_visit_token) }}
                    </a>
                </span>
            </div>
            @endif
            @if(!empty($appointment->pre_visit_answers))
            <div class="detail-row">
                <span class="detail-label">Pre-visit answers</span>
                <span class="detail-value" style="white-space:pre-wrap;font-size:12px;">{{ json_encode($appointment->pre_visit_answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</span>
            </div>
            @endif
            @if($appointment->notes)
            <div class="detail-row">
                <span class="detail-label">Notes</span>
                <span class="detail-value">{{ $appointment->notes }}</span>
            </div>
            @endif
        </div>
        
        {{-- Status Timeline --}}
        <div class="detail-card">
            <h3>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Status Timeline
            </h3>
            
            @php
                $statuses = ['booked', 'confirmed', 'checked_in', 'in_consultation', 'completed'];
                $currentIndex = array_search($appointment->status, $statuses);
                if ($currentIndex === false) $currentIndex = -1;
            @endphp
            
            <div class="timeline">
                @foreach($statuses as $index => $status)
                <div class="timeline-item">
                    <div class="timeline-dot {{ $index < $currentIndex ? 'active' : ($index === $currentIndex ? 'current' : '') }}"></div>
                    <div class="timeline-content">
                        <div class="timeline-title">{{ $statusLabels[$status] }}</div>
                        @if($index <= $currentIndex)
                        <div class="timeline-time">
                            @if($status === 'booked' || $status === 'confirmed')
                            {{ $appointment->created_at->format('d M, h:i A') }}
                            @elseif($status === 'checked_in' && $appointment->status !== 'booked' && $appointment->status !== 'confirmed')
                            Checked in
                            @elseif($status === 'completed' && $appointment->status === 'completed')
                            {{ $appointment->updated_at->format('d M, h:i A') }}
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        
        {{-- Quick Actions --}}
        <div class="detail-card">
            <h3>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
                Quick Actions
            </h3>
            
            <div class="workflow-actions">
                <a href="tel:{{ $patient->phone }}" class="action-btn action-btn-secondary">
                    <svg style="width:16px;height:16px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                    </svg>
                    Call Patient
                </a>
                
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $patient->phone ?? '') }}" target="_blank" class="action-btn action-btn-secondary" style="background:#25D366;color:white;border:none">
                    WhatsApp
                </a>
                
                @if($appointment->status === 'completed' && $appointment->visit)
                <a href="{{ route('billing.create', ['patient_id' => $patient->id, 'visit_id' => $appointment->visit->id]) }}" class="action-btn action-btn-secondary">
                    Create Invoice
                </a>
                @endif
                
                <a href="{{ route('schedule') }}" class="action-btn action-btn-secondary">
                    Back to Schedule
                </a>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<script>console.log('Success: {{ session('success') }}');</script>
@endif

@if(session('error'))
<script>console.log('Error: {{ session('error') }}');</script>
@endif
@endsection
