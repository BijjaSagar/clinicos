@extends('layouts.app')

@section('title', 'Book Appointment')
@section('breadcrumb', 'Schedule / New Appointment')

@push('styles')
<style>
    .booking-container {
        max-width: 800px;
        margin: 0 auto;
    }
    .booking-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        overflow: hidden;
    }
    .booking-header {
        padding: 20px 24px;
        background: linear-gradient(135deg, #1447e6, #0891b2);
        color: white;
    }
    .booking-header h1 {
        font-size: 20px;
        font-weight: 700;
        margin: 0;
    }
    .booking-header p {
        font-size: 13px;
        opacity: 0.8;
        margin: 4px 0 0;
    }
    .booking-body {
        padding: 24px;
    }
    
    .form-section {
        margin-bottom: 24px;
    }
    .form-section-title {
        font-size: 13px;
        font-weight: 700;
        color: #0d1117;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-section-title svg {
        width: 18px;
        height: 18px;
        color: #1447e6;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    .form-grid-3 {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    .form-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .form-input, .form-select, .form-textarea {
        padding: 10px 14px;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        color: #0d1117;
        font-family: inherit;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
        background: white;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        border-color: #1447e6;
        box-shadow: 0 0 0 3px rgba(20, 71, 230, 0.1);
    }
    .form-input::placeholder {
        color: #9ca3af;
    }
    .form-textarea {
        min-height: 80px;
        resize: vertical;
    }
    
    /* Patient Search */
    .patient-search-container {
        position: relative;
    }
    .patient-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        max-height: 200px;
        overflow-y: auto;
        z-index: 20;
        display: none;
    }
    .patient-dropdown.show {
        display: block;
    }
    .patient-option {
        padding: 10px 14px;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.15s;
    }
    .patient-option:last-child {
        border-bottom: none;
    }
    .patient-option:hover {
        background: #eff6ff;
    }
    .patient-option-name {
        font-size: 13px;
        font-weight: 600;
        color: #0d1117;
    }
    .patient-option-phone {
        font-size: 11px;
        color: #6b7280;
    }
    
    /* Time Slots */
    .time-slots {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 8px;
    }
    .time-slot {
        padding: 10px;
        text-align: center;
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.15s;
    }
    .time-slot:hover {
        border-color: #1447e6;
        color: #1447e6;
    }
    .time-slot.selected {
        background: #1447e6;
        border-color: #1447e6;
        color: white;
    }
    .time-slot.unavailable {
        background: #f3f4f6;
        color: #d1d5db;
        cursor: not-allowed;
    }
    
    /* Doctor Cards */
    .doctor-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
    }
    .doctor-card {
        padding: 14px;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.15s;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .doctor-card:hover {
        border-color: #1447e6;
        background: #eff6ff;
    }
    .doctor-card.selected {
        border-color: #1447e6;
        background: #eff6ff;
        box-shadow: 0 0 0 3px rgba(20, 71, 230, 0.15);
    }
    .doctor-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #1447e6, #0891b2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 700;
    }
    .doctor-info h4 {
        font-size: 13px;
        font-weight: 600;
        color: #0d1117;
        margin: 0;
    }
    .doctor-info p {
        font-size: 11px;
        color: #6b7280;
        margin: 2px 0 0;
    }
    
    /* Resource Selection */
    .resource-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 8px;
    }
    .resource-chip {
        padding: 10px 14px;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.15s;
        text-align: center;
    }
    .resource-chip:hover {
        border-color: #1447e6;
        color: #1447e6;
    }
    .resource-chip.selected {
        background: #eff6ff;
        border-color: #1447e6;
        color: #1447e6;
    }
    
    /* Appointment Types */
    .type-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }
    .type-card {
        padding: 16px;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.15s;
    }
    .type-card:hover {
        border-color: #1447e6;
    }
    .type-card.selected {
        border-color: #1447e6;
        background: #eff6ff;
    }
    .type-icon {
        font-size: 24px;
        margin-bottom: 6px;
    }
    .type-name {
        font-size: 12px;
        font-weight: 600;
        color: #0d1117;
    }
    .type-duration {
        font-size: 10px;
        color: #6b7280;
        margin-top: 2px;
    }
    
    /* Actions */
    .booking-actions {
        display: flex;
        gap: 12px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
    }
    .btn {
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-primary {
        background: linear-gradient(135deg, #1447e6, #0891b2);
        color: white;
        border: none;
        flex: 1;
    }
    .btn-primary:hover {
        box-shadow: 0 4px 12px rgba(20, 71, 230, 0.4);
    }
    .btn-secondary {
        background: white;
        color: #6b7280;
        border: 1.5px solid #e5e7eb;
    }
    .btn-secondary:hover {
        border-color: #d1d5db;
        color: #0d1117;
    }
    
    /* Error message */
    .form-error {
        color: #ef4444;
        font-size: 11px;
        margin-top: 4px;
    }
</style>
@endpush

@section('content')
<div class="booking-container" x-data="appointmentForm()">
    <div class="booking-card">
        <div class="booking-header">
            <h1>Book New Appointment</h1>
            <p>Schedule a patient visit with available resources</p>
        </div>
        
        <form action="{{ route('appointments.store') }}" method="POST" class="booking-body">
            @csrf
            
            {{-- Patient Selection --}}
            <div class="form-section">
                <div class="form-section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    Patient
                </div>
                
                <div class="patient-search-container">
                    <input type="text" 
                           class="form-input" 
                           placeholder="Search patient by name or phone..."
                           x-model="patientSearch"
                           @input="searchPatients()"
                           @focus="showPatientDropdown = true"
                           @click.outside="showPatientDropdown = false"
                           style="width:100%">
                    <input type="hidden" name="patient_id" x-model="selectedPatientId" required>
                    
                    <div class="patient-dropdown" :class="{ 'show': showPatientDropdown && filteredPatients.length > 0 }">
                        <template x-for="patient in filteredPatients" :key="patient.id">
                            <div class="patient-option" @click="selectPatient(patient)">
                                <div class="patient-option-name" x-text="patient.name"></div>
                                <div class="patient-option-phone" x-text="patient.phone"></div>
                            </div>
                        </template>
                    </div>
                </div>
                @error('patient_id')
                <div class="form-error">{{ $message }}</div>
                @enderror
                
                <div style="margin-top:8px">
                    <a href="{{ route('patients.create') }}" style="font-size:12px;color:#1447e6;text-decoration:none">
                        + Register new patient
                    </a>
                </div>
            </div>
            
            {{-- Appointment Type --}}
            <div class="form-section">
                <div class="form-section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                    </svg>
                    Appointment Type
                </div>
                
                <div class="type-grid">
                    <div class="type-card" :class="{ 'selected': appointmentType === 'new' }" @click="appointmentType = 'new'">
                        <div class="type-icon">🆕</div>
                        <div class="type-name">New Patient</div>
                        <div class="type-duration">30 mins</div>
                    </div>
                    <div class="type-card" :class="{ 'selected': appointmentType === 'followup' }" @click="appointmentType = 'followup'">
                        <div class="type-icon">🔄</div>
                        <div class="type-name">Follow-up</div>
                        <div class="type-duration">15 mins</div>
                    </div>
                    <div class="type-card" :class="{ 'selected': appointmentType === 'procedure' }" @click="appointmentType = 'procedure'">
                        <div class="type-icon">💉</div>
                        <div class="type-name">Procedure</div>
                        <div class="type-duration">45 mins</div>
                    </div>
                    <div class="type-card" :class="{ 'selected': appointmentType === 'teleconsultation' }" @click="appointmentType = 'teleconsultation'">
                        <div class="type-icon">📱</div>
                        <div class="type-name">Teleconsult</div>
                        <div class="type-duration">20 mins</div>
                    </div>
                </div>
                <input type="hidden" name="appointment_type" x-model="appointmentType">
            </div>
            
            {{-- Doctor Selection --}}
            <div class="form-section">
                <div class="form-section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                    </svg>
                    Select Doctor
                </div>
                
                <div class="doctor-cards">
                    @foreach($doctors as $doctor)
                    <div class="doctor-card" :class="{ 'selected': selectedDoctor === {{ $doctor->id }} }" @click="selectedDoctor = {{ $doctor->id }}">
                        <div class="doctor-avatar">{{ substr($doctor->name, 0, 1) }}</div>
                        <div class="doctor-info">
                            <h4>Dr. {{ $doctor->name }}</h4>
                            <p>{{ $doctor->specialty ?? 'General' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <input type="hidden" name="doctor_id" x-model="selectedDoctor" required>
                @error('doctor_id')
                <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
            
            {{-- Date & Time --}}
            <div class="form-section">
                <div class="form-section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                    Date & Time
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" name="scheduled_date" class="form-input" 
                               x-model="selectedDate" 
                               min="{{ today()->toDateString() }}"
                               value="{{ request('date', today()->toDateString()) }}" 
                               required>
                        @error('scheduled_date')
                        <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration</label>
                        <select name="duration_mins" class="form-select" x-model="duration">
                            <option value="15">15 minutes</option>
                            <option value="30" selected>30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60">1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top:12px">
                    <label class="form-label">Select Time Slot</label>
                    <div class="time-slots">
                        @php
                            $timeSlots = [];
                            for ($h = 9; $h <= 19; $h++) {
                                $timeSlots[] = sprintf('%02d:00', $h);
                                $timeSlots[] = sprintf('%02d:30', $h);
                            }
                        @endphp
                        @foreach($timeSlots as $slot)
                        <div class="time-slot" 
                             :class="{ 'selected': selectedTime === '{{ $slot }}' }"
                             @click="selectedTime = '{{ $slot }}'">
                            {{ \Carbon\Carbon::parse($slot)->format('h:i A') }}
                        </div>
                        @endforeach
                    </div>
                    <input type="hidden" name="scheduled_time" x-model="selectedTime" required>
                    @error('scheduled_time')
                    <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            {{-- Room & Equipment (Optional) --}}
            @php
                $rooms = \DB::table('clinic_rooms')->where('clinic_id', auth()->user()->clinic_id)->where('is_active', true)->get();
                $equipment = \DB::table('clinic_equipment')->where('clinic_id', auth()->user()->clinic_id)->where('is_active', true)->get();
            @endphp
            
            @if($rooms->isNotEmpty() || $equipment->isNotEmpty())
            <div class="form-section">
                <div class="form-section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
                    </svg>
                    Resources (Optional)
                </div>
                
                @if($rooms->isNotEmpty())
                <div class="form-group">
                    <label class="form-label">Room</label>
                    <div class="resource-grid">
                        <div class="resource-chip" :class="{ 'selected': selectedRoom === null }" @click="selectedRoom = null">Any Available</div>
                        @foreach($rooms as $room)
                        <div class="resource-chip" :class="{ 'selected': selectedRoom === {{ $room->id }} }" @click="selectedRoom = {{ $room->id }}">
                            {{ $room->name }}
                        </div>
                        @endforeach
                    </div>
                    <input type="hidden" name="room_id" x-model="selectedRoom">
                </div>
                @endif
                
                @if($equipment->isNotEmpty())
                <div class="form-group" style="margin-top:12px">
                    <label class="form-label">Equipment Required</label>
                    <div class="resource-grid">
                        <div class="resource-chip" :class="{ 'selected': selectedEquipment === null }" @click="selectedEquipment = null">None</div>
                        @foreach($equipment as $equip)
                        <div class="resource-chip" :class="{ 'selected': selectedEquipment === {{ $equip->id }} }" @click="selectedEquipment = {{ $equip->id }}">
                            {{ $equip->name }}
                        </div>
                        @endforeach
                    </div>
                    <input type="hidden" name="equipment_id" x-model="selectedEquipment">
                </div>
                @endif
            </div>
            @endif

            @if(isset($locations) && $locations->count() > 0)
            <div class="form-section">
                <div class="form-section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                    </svg>
                    Clinic location
                </div>
                <div class="form-group">
                    <label class="form-label">Location (optional)</label>
                    <select name="location_id" class="form-select">
                        <option value="">Default / main</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}@if(!empty($loc->is_primary)) (Primary)@endif</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            <div class="form-section" x-show="appointmentType === 'teleconsultation'" x-cloak>
                <div class="form-section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Video visit
                </div>
                <div class="form-group">
                    <label class="form-label">Meeting URL</label>
                    <input type="url" name="teleconsult_meeting_url" class="form-input" placeholder="https://meet.google.com/... or Zoom link">
                </div>
            </div>
            
            {{-- Notes --}}
            <div class="form-section">
                <div class="form-section-title">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                    Notes (Optional)
                </div>
                
                <div class="form-group">
                    <textarea name="notes" class="form-textarea" placeholder="Any special requirements or notes for this appointment..."></textarea>
                </div>
            </div>
            
            {{-- Actions --}}
            <div class="booking-actions">
                <a href="{{ route('schedule') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"/>
                    </svg>
                    Book Appointment
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
console.log('Appointment creation form loaded');

function appointmentForm() {
    return {
        patientSearch: '',
        selectedPatientId: null,
        showPatientDropdown: false,
        filteredPatients: [],
        allPatients: @json($patients),
        
        appointmentType: '{{ request('type', 'new') }}',
        selectedDoctor: {{ request('doctor', $doctors->first()->id ?? 'null') }},
        selectedDate: '{{ request('date', today()->toDateString()) }}',
        selectedTime: '{{ request('time', '09:00') }}',
        duration: '30',
        
        selectedRoom: null,
        selectedEquipment: null,
        
        init() {
            console.log('Appointment form initialized');
            console.log('Patients loaded:', this.allPatients.length);
            
            // Update duration based on appointment type
            this.$watch('appointmentType', (value) => {
                const durations = { 'new': '30', 'followup': '15', 'procedure': '45', 'teleconsultation': '20' };
                this.duration = durations[value] || '30';
            });
        },
        
        searchPatients() {
            if (this.patientSearch.length < 2) {
                this.filteredPatients = [];
                return;
            }
            
            const search = this.patientSearch.toLowerCase();
            this.filteredPatients = this.allPatients.filter(p => 
                p.name.toLowerCase().includes(search) || 
                (p.phone && p.phone.includes(search))
            ).slice(0, 10);
            
            console.log('Filtered patients:', this.filteredPatients.length);
        },
        
        selectPatient(patient) {
            this.selectedPatientId = patient.id;
            this.patientSearch = patient.name + ' (' + patient.phone + ')';
            this.showPatientDropdown = false;
            console.log('Selected patient:', patient.id, patient.name);
        }
    };
}
</script>
@endpush
@endsection
