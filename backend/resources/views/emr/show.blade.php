@extends('layouts.app')

@section('title', 'EMR — ' . ($patient->name ?? 'Patient'))

@section('breadcrumb', 'Patients / ' . ($patient->name ?? 'Patient') . ' / EMR')

@push('styles')
<style>
  :root {
    --blue:#1447e6; --blue-light:#eff3ff; --teal:#0891b2;
    --green:#059669; --green-light:#ecfdf5; --amber:#d97706;
    --red:#dc2626; --dark:#0d1117;
    --text:#1a1f2e; --text2:#4b5563; --text3:#9ca3af;
    --border:#e5e7eb; --bg:#f3f4f6;
  }
  /* PATIENT HEADER */
  .patient-header-bar{background:white;border-bottom:1px solid var(--border);padding:16px 28px;display:flex;align-items:center;gap:20px}
  .pat-avatar{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#ef4444);display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:20px;flex-shrink:0}
  .pat-name{font-family:'Sora',sans-serif;font-size:18px;font-weight:700;color:var(--dark)}
  .pat-meta{display:flex;gap:12px;margin-top:4px;flex-wrap:wrap}
  .meta-chip{display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text2)}
  .meta-chip span{font-weight:600;color:var(--text)}
  .abha-chip{background:linear-gradient(135deg,#f97316,#ef4444);color:white;padding:4px 12px;border-radius:100px;font-size:11px;font-weight:700;display:flex;align-items:center;gap:5px}
  .specialty-badge{background:var(--blue-light);color:var(--blue);padding:4px 12px;border-radius:100px;font-size:11px;font-weight:700}
  .status-pill-active{background:var(--green-light);color:var(--green);padding:4px 12px;border-radius:100px;font-size:11px;font-weight:700;display:flex;align-items:center;gap:5px}
  /* AI STRIP */
  .ai-dictation-strip{background:linear-gradient(135deg,rgba(20,71,230,.05),rgba(8,145,178,.05));border:1.5px solid rgba(20,71,230,.15);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;margin-bottom:16px}
  .ai-strip-icon{width:36px;height:36px;border-radius:9px;background:var(--blue);display:flex;align-items:center;justify-content:center;color:white;font-size:15px;flex-shrink:0}
  .ai-strip-text h4{font-size:13px;font-weight:700;color:var(--dark)}
  .ai-strip-text p{font-size:12px;color:var(--text3);margin-top:2px}
  .ai-strip-actions{margin-left:auto;display:flex;gap:8px}
  /* TABS */
  .emr-tabs{background:white;border-bottom:1px solid var(--border);padding:0 28px;display:flex;gap:2px;flex-shrink:0}
  .emr-tab{padding:14px 18px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;transition:all .15s;white-space:nowrap}
  .emr-tab:hover{color:var(--text2)}
  .emr-tab.active{color:var(--blue);border-bottom-color:var(--blue)}
  /* EMR BODY */
  .emr-body{display:flex;flex:1;min-height:500px;overflow:visible}
  .emr-sidebar{width:280px;flex-shrink:0;background:white;border-right:1px solid var(--border);overflow-y:auto;padding:16px}
  .emr-main{flex:1;overflow-y:auto;padding:24px 28px;padding-bottom:100px;min-height:400px}
  /* TIMELINE */
  .timeline-header{font-size:12px;font-weight:700;color:var(--text3);letter-spacing:.06em;text-transform:uppercase;margin-bottom:12px}
  .visit-card{border:1.5px solid var(--border);border-radius:10px;padding:12px;margin-bottom:8px;cursor:pointer;transition:all .15s}
  .visit-card:hover{border-color:var(--blue);background:var(--blue-light)}
  .visit-card.active-visit{border-color:var(--blue);background:var(--blue-light)}
  .visit-date{font-size:11px;font-weight:700;color:var(--blue)}
  .visit-type{font-size:13px;font-weight:600;color:var(--dark);margin-top:3px}
  .visit-summary{font-size:11px;color:var(--text3);margin-top:4px;line-height:1.5}
  .visit-chips{display:flex;gap:4px;margin-top:6px;flex-wrap:wrap}
  .vchip{padding:2px 8px;border-radius:4px;font-size:10px;font-weight:600}
  /* FORM SECTIONS */
  .form-section{background:white;border:1px solid var(--border);border-radius:12px;margin-bottom:16px;overflow:hidden}
  .form-section-header{padding:14px 20px;background:var(--bg);border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px;cursor:pointer}
  .form-section-header h3{font-size:14px;font-weight:700;color:var(--dark);flex:1}
  .form-section-header .toggle{color:var(--text3);font-size:18px}
  .form-body{padding:20px}
  .form-row{display:grid;gap:12px;margin-bottom:12px}
  .form-row-2{grid-template-columns:1fr 1fr}
  .form-row-3{grid-template-columns:1fr 1fr 1fr}
  .field-group{display:flex;flex-direction:column;gap:4px}
  .field-label{font-size:11px;font-weight:600;color:var(--text3);letter-spacing:.04em;text-transform:uppercase}
  .field-input{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;color:var(--text);font-family:'Inter',sans-serif;outline:none;transition:border-color .15s;background:white;width:100%}
  .field-input:focus{border-color:var(--blue)}
  .field-select{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;color:var(--text);font-family:'Inter',sans-serif;outline:none;background:white;cursor:pointer;width:100%}
  .field-textarea{padding:10px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;color:var(--text);font-family:'Inter',sans-serif;outline:none;resize:vertical;min-height:80px;width:100%;line-height:1.6;transition:border-color .15s}
  .field-textarea:focus{border-color:var(--blue)}
  /* BODY MAP */
  .body-map-container{display:flex;gap:20px;align-items:flex-start}
  .body-diagram{background:var(--bg);border:1.5px solid var(--border);border-radius:12px;padding:20px;text-align:center;cursor:crosshair;position:relative;min-width:160px}
  .body-diagram svg{width:100px}
  .lesion-annotations{flex:1}
  .lesion-row{display:flex;align-items:center;gap:8px;padding:8px 10px;background:var(--bg);border-radius:8px;margin-bottom:6px}
  .lesion-color{width:12px;height:12px;border-radius:50%;flex-shrink:0}
  .lesion-desc{font-size:12px;color:var(--text2);flex:1}
  .lesion-remove{color:var(--text3);cursor:pointer;font-size:14px}
  /* SCALES */
  .scale-group{display:flex;gap:6px;align-items:center;margin-bottom:8px}
  .scale-label{font-size:12px;color:var(--text2);width:120px;flex-shrink:0}
  .scale-input{width:80px;padding:6px 10px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-weight:700;text-align:center;outline:none;font-family:'Inter',sans-serif}
  .scale-input:focus{border-color:var(--blue)}
  .scale-range{font-size:11px;color:var(--text3)}
  .scale-result{font-size:12px;font-weight:700;padding:3px 10px;border-radius:100px}
  .sr-mild{background:var(--green-light);color:var(--green)}
  .sr-mod{background:#fffbeb;color:var(--amber)}
  .sr-sev{background:#fff1f2;color:var(--red)}
  /* PROCEDURE */
  .proc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px}
  .proc-chip{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;font-weight:600;color:var(--text2);cursor:pointer;text-align:center;transition:all .15s}
  .proc-chip:hover{border-color:var(--blue);color:var(--blue)}
  .proc-chip.selected{background:var(--blue);border-color:var(--blue);color:white}
  /* PHOTO */
  .photo-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
  .photo-thumb{aspect-ratio:1;border-radius:8px;overflow:hidden;position:relative;cursor:pointer;border:2px solid transparent;transition:border-color .15s;background:var(--bg)}
  .photo-thumb:hover{border-color:var(--blue)}
  .photo-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:24px;flex-direction:column;gap:4px}
  .photo-label{font-size:10px;color:var(--text3);font-weight:500}
  /* DRUG TABLE */
  .drug-table{width:100%;border-collapse:collapse}
  .drug-table th{font-size:11px;font-weight:600;color:var(--text3);text-align:left;padding:8px 10px;background:var(--bg);letter-spacing:.04em;text-transform:uppercase}
  .drug-table td{font-size:13px;color:var(--text2);padding:10px 10px;border-bottom:1px solid var(--border)}
  .drug-table tr:last-child td{border-bottom:none}
  .drug-table tr:hover td{background:var(--bg)}
  .drug-name{font-weight:600;color:var(--dark)}
  .drug-generic{font-size:11px;color:var(--text3);margin-top:1px}
  .drug-remove{color:var(--text3);cursor:pointer;font-size:16px}
  .add-drug-btn{display:flex;align-items:center;gap:8px;padding:10px 14px;border:1.5px dashed var(--border);border-radius:8px;font-size:13px;color:var(--text3);cursor:pointer;margin-top:8px;transition:all .15s;font-weight:500;background:none}
  .add-drug-btn:hover{border-color:var(--blue);color:var(--blue)}
  /* UPLOAD ZONE */
  .upload-zone{border:2px dashed var(--border);border-radius:10px;padding:32px;text-align:center;cursor:pointer;transition:all .2s}
  .upload-zone:hover{border-color:var(--blue);background:var(--blue-light)}
  /* BOTTOM BAR */
  .bottom-bar{position:sticky;bottom:0;background:white;border-top:1px solid var(--border);padding:14px 28px;display:flex;align-items:center;gap:12px;z-index:10}
  .save-info{font-size:12px;color:var(--text3)}
  .save-info strong{color:var(--green);font-weight:600}
  .status-dot{width:8px;height:8px;border-radius:50%;background:var(--green);animation:pulse2 2s infinite;display:inline-block}
  @keyframes pulse2{0%,100%{opacity:1}50%{opacity:.4}}
  /* DRUG SEARCH */
  .drug-search-container{position:relative;margin-bottom:16px}
  .drug-autocomplete{position:absolute;top:100%;left:0;right:0;background:white;border:1.5px solid var(--border);border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.1);z-index:20;max-height:200px;overflow-y:auto}
  .drug-option{padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border)}
  .drug-option:last-child{border-bottom:none}
  .drug-option:hover{background:var(--blue-light);color:var(--blue)}
  .drug-option strong{color:var(--dark)}
  .drug-option span{color:var(--text3);font-size:11px}
</style>
@endpush

@section('content')
@php
  $patientId = $patient->id;
  $visitId = $visit->id;
  $statusDisplay = match($visit->status) {
    'draft' => 'In Consultation',
    'finalised' => 'Completed',
    default => ucfirst($visit->status ?? 'Unknown'),
  };
  $commonComplaints = $commonComplaints ?? ['General Checkup', 'Follow-up', 'New Complaint'];
@endphp

<div x-data="{ activeTab: 'visit', recording: false, autoSaved: true, lastSaved: '' }" x-init="lastSaved = new Date().toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'}); console.log('Alpine EMR initialized, activeTab:', activeTab)">
<form method="POST" action="{{ route('emr.update', [$patientId, $visitId]) }}" id="emr-form">
  @csrf
  @method('PATCH')

  {{-- TOPBAR --}}
  <div style="background:white;border-bottom:1px solid var(--border);padding:0 28px;height:60px;display:flex;align-items:center;gap:12px;flex-shrink:0;position:sticky;top:0;z-index:20">
    <a href="{{ route('patients.show', $patient) }}" style="font-size:13px;color:var(--text3);text-decoration:none;display:flex;align-items:center;gap:4px">
      <svg style="width:16px;height:16px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
      Back to Patient
    </a>
    <span style="font-size:13px;color:var(--text3)">/</span>
    <span style="font-size:14px;font-weight:700;color:var(--dark)">{{ $patient->name }}</span>
    <div style="display:flex;align-items:center;gap:6px;margin-left:4px">
      @if($visit->status === 'draft')
      <div class="status-dot"></div>
      <span style="font-size:12px;color:var(--green);font-weight:600">{{ $statusDisplay }}</span>
      @else
      <span style="font-size:12px;color:var(--text3);font-weight:600">{{ $statusDisplay }}</span>
      @endif
    </div>
    <div style="margin-left:auto;display:flex;gap:8px">
      <a href="{{ route('whatsapp.index') }}" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:transparent;color:var(--text2);text-decoration:none">💬 WhatsApp</a>
      <a href="{{ route('billing.create') }}?patient_id={{ $patient->id }}" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:transparent;color:var(--text2);text-decoration:none">🧾 Create Invoice</a>
      @if($visit->status !== 'finalised')
      <form action="{{ route('emr.finalise', [$patient, $visit]) }}" method="POST" style="display:inline">
        @csrf
        <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;background:var(--green);color:white">✓ Complete Visit</button>
      </form>
      @endif
    </div>
  </div>

  {{-- PATIENT HEADER BAR --}}
  <div class="patient-header-bar">
    <div class="pat-avatar">{{ substr($patient->name, 0, 1) }}</div>
    <div>
      <div class="pat-name">{{ $patient->name }}</div>
      <div class="pat-meta">
        <div class="meta-chip">Age: <span>{{ $patient->age_years ?? 'N/A' }}{{ $patient->sex ? strtoupper(substr($patient->sex, 0, 1)) : '' }}</span></div>
        @if($patient->dob)
        <div class="meta-chip">DOB: <span>{{ \Carbon\Carbon::parse($patient->dob)->format('d M Y') }}</span></div>
        @endif
        <div class="meta-chip">📞 <span>{{ $patient->phone }}</span></div>
        @if($patient->blood_group)
        <div class="meta-chip">Blood: <span>{{ $patient->blood_group }}</span></div>
        @endif
        <div class="meta-chip">Visit #: <span>{{ $visit->visit_number ?? $patient->visit_count ?? 1 }}</span></div>
        @if($patient->abha_id)
        <div class="abha-chip">🛡️ ABHA: {{ $patient->abha_id }}</div>
        @endif
        @if($visit->specialty)
        <div class="specialty-badge">{{ ucfirst($visit->specialty) }}</div>
        @endif
        @if($visit->status === 'draft')
        <div class="status-pill-active">
          <div class="status-dot"></div>
          In Consultation
        </div>
        @endif
      </div>
    </div>
    <div class="pat-actions" style="margin-left:auto;display:flex;gap:8px">
      <a href="{{ route('patients.edit', $patient) }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:transparent;color:var(--text2);text-decoration:none">Edit Profile</a>
      <a href="{{ route('patients.show', $patient) }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:transparent;color:var(--text2);text-decoration:none">Medical History</a>
    </div>
  </div>

  {{-- TAB NAVIGATION --}}
  @php $photoCount = ($patientPhotos ?? collect())->flatten()->count(); @endphp
  <div class="emr-tabs">
    <div class="emr-tab" :class="{ 'active': activeTab === 'visit' }" @click="activeTab = 'visit'">📋 Visit Note</div>
    <div class="emr-tab" :class="{ 'active': activeTab === 'prescription' }" @click="activeTab = 'prescription'">💊 Prescription @if($prescription)({{ $prescription->drugs->count() }})@endif</div>
    <div class="emr-tab" :class="{ 'active': activeTab === 'photos' }" @click="activeTab = 'photos'">📷 Photos @if($photoCount > 0)({{ $photoCount }})@endif</div>
    <div class="emr-tab" :class="{ 'active': activeTab === 'progress' }" @click="activeTab = 'progress'">📈 Progress</div>
    <div class="emr-tab" :class="{ 'active': activeTab === 'investigations' }" @click="activeTab = 'investigations'">🔬 Investigations @if(($labOrders ?? collect())->count() > 0)({{ $labOrders->count() }})@endif</div>
    <div class="emr-tab" :class="{ 'active': activeTab === 'billing' }" @click="activeTab = 'billing'">🧾 Billing @if($visit->invoice)✓@endif</div>
  </div>

  {{-- EMR BODY --}}
  <div class="emr-body">

    {{-- VISIT TIMELINE SIDEBAR --}}
    <div class="emr-sidebar">
      <div class="timeline-header">Visit History</div>

      @foreach($visitHistory ?? [] as $historyVisit)
      <a href="{{ route('emr.show', [$patient, $historyVisit]) }}" 
         class="visit-card {{ $historyVisit->id === $visit->id ? 'active-visit' : '' }}" 
         style="text-decoration:none;display:block">
        <div class="visit-date">
          @if($historyVisit->created_at->isToday())
            Today · {{ $historyVisit->created_at->format('d M Y') }}
          @else
            {{ $historyVisit->created_at->format('d M Y') }}
          @endif
        </div>
        <div class="visit-type">
          @if($historyVisit->visit_number === 1)
            Initial Consultation
          @else
            Follow-up #{{ $historyVisit->visit_number }}
          @endif
          @if($historyVisit->chief_complaint)
            · {{ Str::limit($historyVisit->chief_complaint, 20) }}
          @endif
        </div>
        @if($historyVisit->diagnosis_text)
        <div class="visit-summary">{{ Str::limit($historyVisit->diagnosis_text, 60) }}</div>
        @endif
        <div class="visit-chips">
          @if($historyVisit->id === $visit->id)
            <span class="vchip" style="background:#ecfdf5;color:#059669">Current</span>
          @endif
          @if($historyVisit->status === 'finalised')
            <span class="vchip" style="background:#f1f5f9;color:#64748b">Completed</span>
          @elseif($historyVisit->status === 'draft')
            <span class="vchip" style="background:#eff3ff;color:#1447e6">In Progress</span>
          @endif
          @if($historyVisit->prescriptions->isNotEmpty())
            <span class="vchip" style="background:#f1f5f9;color:#64748b">Rx</span>
          @endif
        </div>
      </a>
      @endforeach

      @if(($visitHistory ?? collect())->isEmpty())
      <div style="padding:16px;text-align:center;color:var(--text3);font-size:12px">
        This is the first visit for this patient
      </div>
      @endif

      {{-- Alerts Section --}}
      <div style="margin-top:16px;padding:12px;background:var(--bg);border-radius:10px">
        <div style="font-size:11px;font-weight:700;color:var(--text3);letter-spacing:.05em;text-transform:uppercase;margin-bottom:10px">Alerts</div>
        <div style="display:flex;flex-direction:column;gap:6px">
          @if($patient->known_allergies)
          <div style="display:flex;gap:6px;align-items:flex-start;font-size:11px">
            <span>⚠️</span>
            <span style="color:var(--red);font-weight:500">Allergies: {{ $patient->known_allergies }}</span>
          </div>
          @endif
          
          @if($previousPrescriptions && $previousPrescriptions->isNotEmpty())
          @php $lastRx = $previousPrescriptions->first(); @endphp
          <div style="display:flex;gap:6px;align-items:flex-start;font-size:11px">
            <span>💊</span>
            <span style="color:var(--text2)">Last Rx: {{ $lastRx->created_at->format('d M') }} — 
              @foreach($lastRx->drugs->take(2) as $drug)
                {{ $drug->drug_name }}{{ !$loop->last ? ', ' : '' }}
              @endforeach
              @if($lastRx->drugs->count() > 2) +{{ $lastRx->drugs->count() - 2 }} more @endif
            </span>
          </div>
          @endif

          @php $photoCount = ($patientPhotos ?? collect())->flatten()->count(); @endphp
          @if($photoCount > 0)
          <div style="display:flex;gap:6px;align-items:flex-start;font-size:11px">
            <span>📸</span>
            <span style="color:var(--blue);font-weight:500">{{ $photoCount }} photos on file</span>
          </div>
          @endif

          @if($patient->chronic_conditions)
          <div style="display:flex;gap:6px;align-items:flex-start;font-size:11px">
            <span>🩺</span>
            <span style="color:var(--amber);font-weight:500">{{ Str::limit($patient->chronic_conditions, 50) }}</span>
          </div>
          @endif

          @if(!$patient->known_allergies && (!$previousPrescriptions || $previousPrescriptions->isEmpty()) && $photoCount === 0)
          <div style="font-size:11px;color:var(--text3)">No alerts for this patient</div>
          @endif
        </div>
      </div>
    </div>

    {{-- MAIN EMR AREA --}}
    <div class="emr-main">

      {{-- ══════ VISIT NOTE TAB ══════ --}}
      <div x-show="activeTab === 'visit'">

        {{-- AI DICTATION STRIP --}}
        <div class="ai-dictation-strip">
          <div class="ai-strip-icon">🎙️</div>
          <div class="ai-strip-text">
            <h4 x-text="recording ? '🔴 Recording… tap to stop' : 'AI Dictation Mode'"></h4>
            <p>Tap to dictate findings — AI will auto-fill the fields below in your specialty template</p>
          </div>
          <div class="ai-strip-actions">
            <button type="button"
              @click="recording = !recording"
              :style="recording ? 'background:#dc2626' : ''"
              style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;background:var(--blue);color:white;transition:all .2s">
              <span x-text="recording ? '⏹ Stop' : '🎤 Start Dictation'"></span>
            </button>
            <button type="button" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:transparent;color:var(--text2)">✨ AI Summarise</button>
          </div>
        </div>

        {{-- CHIEF COMPLAINT & HISTORY --}}
        <div class="form-section" x-data="{sectionOpen:true}">
          <div class="form-section-header" @click="sectionOpen=!sectionOpen">
            <h3>Chief Complaint &amp; History</h3>
            <span class="toggle" x-text="sectionOpen ? '−' : '+'"></span>
          </div>
          <div class="form-body" x-show="sectionOpen">
            <div class="form-row form-row-2">
              <div class="field-group">
                <div class="field-label">Chief Complaint</div>
                <select name="chief_complaint" class="field-select" @change="window.triggerAutoSave()">
                  <option value="">Select complaint...</option>
                  @foreach($commonComplaints as $complaint)
                  <option value="{{ $complaint }}" {{ ($visit->chief_complaint ?? '') === $complaint ? 'selected' : '' }}>{{ $complaint }}</option>
                  @endforeach
                  <option value="other" {{ !in_array($visit->chief_complaint ?? '', $commonComplaints) && $visit->chief_complaint ? 'selected' : '' }}>Other</option>
                </select>
              </div>
              <div class="field-group">
                <div class="field-label">Duration</div>
                <input name="duration" class="field-input" value="{{ $visit->getStructuredField('duration', '') }}" type="text" placeholder="e.g. 2 weeks, 3 months" @input="window.triggerAutoSave()"/>
              </div>
            </div>
            <div class="form-row form-row-3">
              <div class="field-group">
                <div class="field-label">Onset</div>
                <select name="onset" class="field-select" @change="window.triggerAutoSave()">
                  <option value="">Select...</option>
                  <option value="gradual" {{ $visit->getStructuredField('onset') === 'gradual' ? 'selected' : '' }}>Gradual</option>
                  <option value="sudden" {{ $visit->getStructuredField('onset') === 'sudden' ? 'selected' : '' }}>Sudden</option>
                  <option value="recurrent" {{ $visit->getStructuredField('onset') === 'recurrent' ? 'selected' : '' }}>Recurrent</option>
                </select>
              </div>
              <div class="field-group">
                <div class="field-label">Progression</div>
                <select name="progression" class="field-select" @change="window.triggerAutoSave()">
                  <option value="">Select...</option>
                  <option value="worsening" {{ $visit->getStructuredField('progression') === 'worsening' ? 'selected' : '' }}>Worsening</option>
                  <option value="improving" {{ $visit->getStructuredField('progression') === 'improving' ? 'selected' : '' }}>Improving</option>
                  <option value="static" {{ $visit->getStructuredField('progression') === 'static' ? 'selected' : '' }}>Static</option>
                  <option value="fluctuating" {{ $visit->getStructuredField('progression') === 'fluctuating' ? 'selected' : '' }}>Fluctuating</option>
                </select>
              </div>
              <div class="field-group">
                <div class="field-label">Previous Treatment</div>
                <select name="previous_treatment" class="field-select" @change="window.triggerAutoSave()">
                  <option value="">Select...</option>
                  <option value="yes_ongoing" {{ $visit->getStructuredField('previous_treatment') === 'yes_ongoing' ? 'selected' : '' }}>Yes — On treatment</option>
                  <option value="yes_stopped" {{ $visit->getStructuredField('previous_treatment') === 'yes_stopped' ? 'selected' : '' }}>Yes — Stopped</option>
                  <option value="no" {{ $visit->getStructuredField('previous_treatment') === 'no' ? 'selected' : '' }}>No</option>
                </select>
              </div>
            </div>
            <div class="field-group">
              <div class="field-label">History Notes</div>
              <textarea name="history" class="field-textarea" placeholder="Document relevant medical history, symptoms, triggers, and any other relevant information..." @input="window.triggerAutoSave()">{{ $visit->history }}</textarea>
            </div>
          </div>
        </div>

        {{-- BODY MAP --}}
        <div class="form-section">
          <div class="form-section-header">
            <h3>Lesion Map &amp; Skin Findings</h3>
            <span class="toggle">−</span>
          </div>
          <div class="form-body">
            <div class="body-map-container">
              <div>
                <div class="body-diagram" title="Tap to add lesion">
                  <div style="font-size:11px;font-weight:600;color:var(--text3);margin-bottom:10px">Tap to annotate</div>
                  <svg viewBox="0 0 80 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="40" cy="16" rx="14" ry="15" fill="#d1d5db"/>
                    <rect x="22" y="32" width="36" height="48" rx="8" fill="#d1d5db"/>
                    <rect x="7" y="33" width="14" height="38" rx="7" fill="#d1d5db"/>
                    <rect x="59" y="33" width="14" height="38" rx="7" fill="#d1d5db"/>
                    <rect x="23" y="82" width="15" height="50" rx="7" fill="#d1d5db"/>
                    <rect x="42" y="82" width="15" height="50" rx="7" fill="#d1d5db"/>
                    {{-- Lesion dots --}}
                    <circle cx="36" cy="18" r="5" fill="#ef4444" opacity=".8"/>
                    <circle cx="46" cy="14" r="4" fill="#ef4444" opacity=".6"/>
                    <circle cx="33" cy="28" r="3.5" fill="#f59e0b" opacity=".8"/>
                    <circle cx="50" cy="42" r="4" fill="#6366f1" opacity=".6"/>
                    <circle cx="12" cy="45" r="4.5" fill="#6366f1" opacity=".7"/>
                  </svg>
                  <div style="font-size:10px;color:var(--text3);margin-top:8px">Front View</div>
                </div>
              </div>
              <div class="lesion-annotations">
                <div style="font-size:11px;font-weight:700;color:var(--text3);letter-spacing:.06em;text-transform:uppercase;margin-bottom:10px">Recorded Lesions</div>
                
                @forelse($visit->lesions ?? [] as $lesion)
                <div class="lesion-row" data-lesion-id="{{ $lesion->id }}">
                  <div class="lesion-color" style="background:{{ $lesion->colour ?? '#ef4444' }}"></div>
                  <div class="lesion-desc">
                    <strong style="color:var(--dark)">{{ $lesion->body_region }}</strong> · {{ $lesion->lesion_type }}
                    @if($lesion->size_cm) · {{ $lesion->size_cm }} cm @endif
                    @if($lesion->notes)
                    <div style="font-size:11px;color:var(--text3)">{{ $lesion->notes }}</div>
                    @endif
                    @if($lesion->distribution || $lesion->surface)
                    <div style="font-size:11px;color:var(--text3)">
                      @if($lesion->distribution)Distribution: {{ $lesion->distribution }}@endif
                      @if($lesion->distribution && $lesion->surface) · @endif
                      @if($lesion->surface)Surface: {{ $lesion->surface }}@endif
                    </div>
                    @endif
                  </div>
                  <button type="button" class="lesion-remove" onclick="removeLesion({{ $lesion->id }})" title="Remove">✕</button>
                </div>
                @empty
                <div style="padding:16px;text-align:center;color:var(--text3);font-size:12px;background:var(--bg);border-radius:8px">
                  No lesions recorded yet. Click the body diagram to add annotations.
                </div>
                @endforelse
                
                <button type="button" 
                        onclick="openAddLesionModal()"
                        style="display:flex;align-items:center;gap:6px;background:none;border:1.5px dashed var(--border);border-radius:8px;padding:8px 14px;font-size:12px;color:var(--text3);cursor:pointer;margin-top:8px;font-family:'Inter',sans-serif;width:100%;justify-content:center">
                  ＋ Add lesion annotation
                </button>
              </div>
            </div>
          </div>
        </div>

        {{-- GRADING SCALES (Specialty-specific) --}}
        <div class="form-section">
          <div class="form-section-header">
            <h3>Clinical Grading Scales</h3>
            <span class="toggle">−</span>
          </div>
          <div class="form-body">
            @php
              $scales = $visit->scales ?? collect();
              $pasiScale = $scales->firstWhere('scale_name', 'PASI');
              $igaScale = $scales->firstWhere('scale_name', 'IGA');
              $dlqiScale = $scales->firstWhere('scale_name', 'DLQI');
            @endphp
            
            <div class="scale-group">
              <div class="scale-label">PASI Score</div>
              <input name="scales[pasi]" class="scale-input" value="{{ $pasiScale?->score ?? '' }}" type="number" step="0.1" min="0" max="72" placeholder="0-72" @input="window.triggerAutoSave()"/>
              <div class="scale-range">(0–72)</div>
              @if($pasiScale)
                @php
                  $pasiSeverity = $pasiScale->score <= 5 ? 'mild' : ($pasiScale->score <= 12 ? 'mod' : 'sev');
                  $pasiLabel = $pasiScale->score <= 5 ? 'Mild' : ($pasiScale->score <= 12 ? 'Moderate' : 'Severe');
                @endphp
                <div class="scale-result sr-{{ $pasiSeverity }}">{{ $pasiLabel }}</div>
              @endif
              @if(isset($scaleChanges['PASI']))
                <div style="font-size:11px;color:var(--text3);margin-left:8px">
                  {{ $scaleChanges['PASI']['change'] > 0 ? '↑' : '↓' }}{{ abs($scaleChanges['PASI']['change']) }} vs last
                </div>
              @endif
            </div>
            
            <div class="scale-group">
              <div class="scale-label">IGA Grade</div>
              <input name="scales[iga]" class="scale-input" value="{{ $igaScale?->score ?? '' }}" type="number" step="1" min="0" max="4" placeholder="0-4" @input="window.triggerAutoSave()"/>
              <div class="scale-range">(0–4)</div>
              @if($igaScale)
                @php
                  $igaSeverity = $igaScale->score <= 1 ? 'mild' : ($igaScale->score <= 2 ? 'mod' : 'sev');
                  $igaLabel = match((int)$igaScale->score) { 0 => 'Clear', 1 => 'Almost Clear', 2 => 'Mild', 3 => 'Moderate', 4 => 'Severe', default => 'Unknown' };
                @endphp
                <div class="scale-result sr-{{ $igaSeverity }}">{{ $igaLabel }}</div>
              @endif
              @if(isset($scaleChanges['IGA']))
                <div style="font-size:11px;color:var(--text3);margin-left:8px">
                  @if($scaleChanges['IGA']['change'] === 0) Unchanged @else {{ $scaleChanges['IGA']['change'] > 0 ? '↑' : '↓' }}{{ abs($scaleChanges['IGA']['change']) }} @endif
                </div>
              @endif
            </div>
            
            <div class="scale-group">
              <div class="scale-label">DLQI Score</div>
              <input name="scales[dlqi]" class="scale-input" value="{{ $dlqiScale?->score ?? '' }}" type="number" step="1" min="0" max="30" placeholder="0-30" @input="window.triggerAutoSave()"/>
              <div class="scale-range">(0–30)</div>
              @if($dlqiScale)
                @php
                  $dlqiSeverity = $dlqiScale->score <= 5 ? 'mild' : ($dlqiScale->score <= 10 ? 'mod' : 'sev');
                  $dlqiLabel = $dlqiScale->score <= 1 ? 'No effect' : ($dlqiScale->score <= 5 ? 'Small effect' : ($dlqiScale->score <= 10 ? 'Moderate effect' : 'Large effect on QoL'));
                @endphp
                <div class="scale-result sr-{{ $dlqiSeverity }}">{{ $dlqiLabel }}</div>
              @endif
              @if(isset($scaleChanges['DLQI']))
                <div style="font-size:11px;color:var(--text3);margin-left:8px">
                  {{ $scaleChanges['DLQI']['change'] > 0 ? '↑' : '↓' }}{{ abs($scaleChanges['DLQI']['change']) }} vs last
                </div>
              @endif
            </div>

            @if($previousVisit && $previousVisit->scales->isNotEmpty())
            <div style="margin-top:12px;padding:12px;background:var(--blue-light);border-radius:8px;font-size:12px;color:var(--blue)">
              <strong>vs Last Visit ({{ $previousVisit->created_at->format('d M') }}):</strong>
              @foreach($scaleChanges as $name => $change)
                {{ $name }} {{ $change['previous'] }} → {{ $change['current'] }} 
                ({{ $change['change'] > 0 ? '↑' : ($change['change'] < 0 ? '↓' : '=') }}{{ abs($change['change']) }}){{ !$loop->last ? ' · ' : '' }}
              @endforeach
            </div>
            @endif
          </div>
        </div>

        {{-- PROCEDURE PERFORMED --}}
        <div class="form-section">
          <div class="form-section-header">
            <h3>Procedure Performed Today</h3>
            <span class="toggle">−</span>
          </div>
          <div class="form-body" x-data="{
            selectedProcs: {{ json_encode($visit->procedures->pluck('procedure_name')->toArray()) }},
            procs: {{ json_encode($availableProcedures ?? ['Chemical Peel', 'LASER', 'PRP', 'Botox', 'Fillers', 'Microneedling']) }},
            toggleProc(proc) {
              const idx = this.selectedProcs.indexOf(proc);
              if(idx >= 0) this.selectedProcs.splice(idx, 1);
              else this.selectedProcs.push(proc);
            }
          }">
            <div style="margin-bottom:12px">
              <div class="field-label" style="margin-bottom:8px">Select Procedure</div>
              <div class="proc-grid">
                <template x-for="proc in procs" :key="proc">
                  <div class="proc-chip"
                       :class="selectedProcs.includes(proc) && 'selected'"
                       @click="toggleProc(proc); window.triggerAutoSave()"
                       x-text="proc"></div>
                </template>
              </div>
              <template x-for="proc in selectedProcs" :key="proc">
                <input type="hidden" name="procedures[]" :value="proc"/>
              </template>
            </div>
            
            @php 
              $firstProc = $visit->procedures->first(); 
              $procParams = $firstProc?->parameters ?? [];
            @endphp
            <div class="form-row form-row-3">
              <div class="field-group">
                <div class="field-label">Agent / Product</div>
                <input name="procedure_agent" class="field-input" type="text" value="{{ $procParams['agent'] ?? '' }}" placeholder="e.g. Salicylic Acid 30%" @input="window.triggerAutoSave()"/>
              </div>
              <div class="field-group">
                <div class="field-label">Areas Treated</div>
                <input name="areas_treated" class="field-input" value="{{ $firstProc?->body_region ?? '' }}" type="text" placeholder="e.g. Full face, T-zone" @input="window.triggerAutoSave()"/>
              </div>
              <div class="field-group">
                <div class="field-label">Session No.</div>
                <input name="session_number" class="field-input" value="{{ $procParams['session_number'] ?? '' }}" type="text" placeholder="e.g. 3 of 6" @input="window.triggerAutoSave()"/>
              </div>
            </div>
            <div class="field-group">
              <div class="field-label">Procedure Notes</div>
              <textarea name="procedure_notes" class="field-textarea" style="min-height:60px" placeholder="Document procedure details, patient tolerance, post-procedure care instructions..." @input="window.triggerAutoSave()">{{ $firstProc?->notes ?? '' }}</textarea>
            </div>
          </div>
        </div>

        {{-- PLAN & FOLLOW-UP --}}
        <div class="form-section">
          <div class="form-section-header">
            <h3>Plan &amp; Follow-up</h3>
            <span class="toggle">−</span>
          </div>
          <div class="form-body">
            <div class="form-row form-row-2">
              <div class="field-group">
                <div class="field-label">Follow-up In (Days)</div>
                <select name="followup_in_days" class="field-select" @change="updateFollowupDate($event.target.value); triggerAutoSave()">
                  <option value="">Select...</option>
                  <option value="7" {{ ($visit->followup_in_days ?? 0) == 7 ? 'selected' : '' }}>1 week</option>
                  <option value="14" {{ ($visit->followup_in_days ?? 0) == 14 ? 'selected' : '' }}>2 weeks</option>
                  <option value="28" {{ ($visit->followup_in_days ?? 0) == 28 ? 'selected' : '' }}>4 weeks</option>
                  <option value="42" {{ ($visit->followup_in_days ?? 0) == 42 ? 'selected' : '' }}>6 weeks</option>
                  <option value="90" {{ ($visit->followup_in_days ?? 0) == 90 ? 'selected' : '' }}>3 months</option>
                  <option value="0" {{ ($visit->followup_in_days ?? 0) == 0 && $visit->followup_date === null ? 'selected' : '' }}>As needed</option>
                </select>
              </div>
              <div class="field-group">
                <div class="field-label">Follow-up Date</div>
                <input name="followup_date" id="followup_date" class="field-input" type="date" value="{{ $visit->followup_date?->format('Y-m-d') ?? '' }}" @input="window.triggerAutoSave()"/>
              </div>
            </div>
            <div class="form-row form-row-2">
              <div class="field-group">
                <div class="field-label">Diagnosis</div>
                <input name="diagnosis_text" class="field-input" type="text" value="{{ $visit->diagnosis_text ?? '' }}" placeholder="e.g. Acne vulgaris, Psoriasis" @input="window.triggerAutoSave()"/>
              </div>
              <div class="field-group">
                <div class="field-label">ICD-10 Code (Optional)</div>
                <input name="diagnosis_code" class="field-input" type="text" value="{{ $visit->diagnosis_code ?? '' }}" placeholder="e.g. L70.0" @input="window.triggerAutoSave()"/>
              </div>
            </div>
            <div class="field-group">
              <div class="field-label">Plan Notes</div>
              <textarea name="plan" class="field-textarea" style="min-height:60px" placeholder="Document treatment plan, patient counselling, and any special instructions..." @input="window.triggerAutoSave()">{{ $visit->plan }}</textarea>
            </div>
          </div>
        </div>

      </div>{{-- /visit note tab --}}

      {{-- ══════ PRESCRIPTION TAB ══════ --}}
      <div x-show="activeTab === 'prescription'" x-cloak
           x-data="{
             drugs: {{ json_encode(($prescription?->drugs ?? collect())->map(fn($d) => [
               'id' => $d->id,
               'name' => $d->drug_name,
               'generic' => $d->generic_name,
               'dose' => $d->dose,
               'frequency' => $d->frequency,
               'duration' => $d->duration,
               'instructions' => $d->instructions,
             ])->toArray()) }},
             drugSearch: '',
             showSuggestions: false,
             suggestions: [],
             async searchDrugs() {
               if (this.drugSearch.length < 2) {
                 this.suggestions = [];
                 this.showSuggestions = false;
                 return;
               }
               try {
                 const res = await fetch('{{ route('api.drugs.search') }}?q=' + encodeURIComponent(this.drugSearch));
                 this.suggestions = await res.json();
                 this.showSuggestions = this.suggestions.length > 0;
               } catch (e) {
                 console.error('Drug search failed:', e);
               }
             },
             addDrug(drug) {
               this.drugs.push({
                 id: null,
                 name: drug.brand_name,
                 generic: drug.generic_name,
                 dose: '',
                 frequency: '',
                 duration: '',
                 instructions: ''
               });
               this.drugSearch = '';
               this.showSuggestions = false;
               this.triggerAutoSave();
             },
             removeDrug(index) {
               this.drugs.splice(index, 1);
               this.triggerAutoSave();
             },
             triggerAutoSave() {
               window.dispatchEvent(new CustomEvent('emr-autosave'));
             }
           }">

        <div class="form-section">
          <div class="form-section-header">
            <h3>Prescription</h3>
            <a href="#" onclick="window.print(); return false;" style="font-size:12px;color:var(--blue);font-weight:600;text-decoration:none;margin-left:auto">🖨 Print Preview</a>
          </div>
          <div class="form-body">

            {{-- Drug search --}}
            <div class="drug-search-container">
              <input type="text"
                class="field-input"
                placeholder="🔍 Search drugs — type brand or generic name..."
                x-model="drugSearch"
                @input.debounce.300ms="searchDrugs()"
                @focus="showSuggestions = suggestions.length > 0"
                @blur="setTimeout(() => showSuggestions = false, 200)"
                style="padding-left:14px"/>
              <div class="drug-autocomplete" x-show="showSuggestions" x-cloak>
                <template x-for="drug in suggestions" :key="drug.id || drug.brand_name">
                  <div class="drug-option" @mousedown="addDrug(drug)">
                    <strong x-text="drug.brand_name"></strong>
                    <span x-text="' · ' + (drug.generic_name || drug.composition || '')"></span>
                    <span x-text="drug.manufacturer ? ' · ' + drug.manufacturer : ''"></span>
                  </div>
                </template>
              </div>
            </div>

            {{-- Prescription table --}}
            <table class="drug-table">
              <thead>
                <tr>
                  <th>Drug Name</th>
                  <th>Dose</th>
                  <th>Frequency</th>
                  <th>Duration</th>
                  <th>Instructions</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <template x-for="(drug, index) in drugs" :key="index">
                  <tr>
                    <td>
                      <div class="drug-name" x-text="drug.name"></div>
                      <div class="drug-generic" x-text="drug.generic || ''"></div>
                      <input type="hidden" :name="'drugs['+index+'][name]'" :value="drug.name"/>
                      <input type="hidden" :name="'drugs['+index+'][generic]'" :value="drug.generic"/>
                    </td>
                    <td><input type="text" :name="'drugs['+index+'][dose]'" x-model="drug.dose" class="field-input" style="width:80px;padding:4px 8px" placeholder="e.g. 1 tab" @input="window.triggerAutoSave()"/></td>
                    <td><input type="text" :name="'drugs['+index+'][frequency]'" x-model="drug.frequency" class="field-input" style="width:120px;padding:4px 8px" placeholder="e.g. BD" @input="window.triggerAutoSave()"/></td>
                    <td><input type="text" :name="'drugs['+index+'][duration]'" x-model="drug.duration" class="field-input" style="width:90px;padding:4px 8px" placeholder="e.g. 2 weeks" @input="window.triggerAutoSave()"/></td>
                    <td><input type="text" :name="'drugs['+index+'][instructions]'" x-model="drug.instructions" class="field-input" style="min-width:180px;padding:4px 8px" placeholder="Special instructions" @input="window.triggerAutoSave()"/></td>
                    <td><span class="drug-remove" @click="removeDrug(index)" title="Remove">✕</span></td>
                  </tr>
                </template>
                <tr x-show="drugs.length === 0">
                  <td colspan="6" style="text-align:center;padding:24px;color:var(--text3)">
                    No drugs added yet. Search and add drugs above.
                  </td>
                </tr>
              </tbody>
            </table>
            
            <button type="button" class="add-drug-btn" @click="$refs.drugSearchInput?.focus()">
              ＋ Add drug from database
            </button>
            
            @if($patient->known_allergies)
            <div style="margin-top:12px;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:12px;color:#dc2626">
              ⚠️ Patient Allergies: {{ $patient->known_allergies }}
            </div>
            @endif
          </div>
        </div>

      </div>{{-- /prescription tab --}}

      {{-- ══════ PHOTOS TAB ══════ --}}
      <div x-show="activeTab === 'photos'" x-cloak>
        <div class="form-section">
          <div class="form-section-header">
            <h3>Photo Vault</h3>
            @php $totalPhotos = ($patientPhotos ?? collect())->flatten()->count(); @endphp
            <span style="font-size:12px;color:var(--text3)">{{ $totalPhotos }} photos</span>
          </div>
          <div class="form-body">
            {{-- Upload zone --}}
            <form action="{{ route('patients.upload-photo', $patient) }}" method="POST" enctype="multipart/form-data" class="upload-zone" style="margin-bottom:20px">
              @csrf
              <input type="hidden" name="visit_id" value="{{ $visit->id }}"/>
              <div style="font-size:32px;margin-bottom:8px">📷</div>
              <p style="font-size:14px;font-weight:600;color:var(--text2)">Drop photos here or <span style="color:var(--blue)">click to upload</span></p>
              <p style="font-size:12px;color:var(--text3);margin-top:4px">JPEG, PNG · Max 10MB each</p>
              <div style="margin-top:16px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
                <label style="cursor:pointer">
                  <input type="file" name="photo" accept="image/*" style="display:none" onchange="this.form.querySelector('[name=photo_type]').value='before'; this.form.submit()"/>
                  <input type="hidden" name="photo_type" value="before"/>
                  <span style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;background:var(--blue);color:white">📸 Before Photo</span>
                </label>
                <label style="cursor:pointer">
                  <input type="file" name="photo" accept="image/*" style="display:none" onchange="this.form.querySelector('[name=photo_type]').value='after'; this.form.submit()"/>
                  <span style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;background:var(--teal);color:white">📸 After Photo</span>
                </label>
                <label style="cursor:pointer">
                  <input type="file" name="photo" accept="image/*" style="display:none" onchange="this.form.querySelector('[name=photo_type]').value='progress'; this.form.submit()"/>
                  <span style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;border:1px solid var(--border);color:var(--text2)">📸 Progress Photo</span>
                </label>
              </div>
            </form>

            {{-- Photo grid --}}
            <div style="font-size:12px;font-weight:700;color:var(--text3);letter-spacing:.05em;text-transform:uppercase;margin-bottom:10px">Patient Photos</div>
            <div class="photo-grid">
              @php $allPhotos = ($patientPhotos ?? collect())->flatten(); @endphp
              @forelse($allPhotos as $photo)
              <div class="photo-thumb" style="position:relative;overflow:hidden">
                <img src="{{ route('patients.view-photo', [$patient, $photo]) }}" 
                     alt="{{ $photo->photo_type ?? 'Photo' }}" 
                     style="width:100%;height:100%;object-fit:cover"/>
                <div style="position:absolute;bottom:0;left:0;right:0;padding:6px;background:linear-gradient(transparent,rgba(0,0,0,.7));color:white">
                  <div style="font-size:10px;font-weight:600;text-transform:uppercase">{{ $photo->photo_type ?? 'Photo' }}</div>
                  <div style="font-size:9px;opacity:0.8">{{ $photo->created_at->format('d M Y') }}</div>
                </div>
              </div>
              @empty
              <div style="grid-column:span 4;text-align:center;padding:32px;color:var(--text3)">
                <div style="font-size:32px;margin-bottom:8px">📷</div>
                <p style="font-size:13px">No photos uploaded yet</p>
                <p style="font-size:11px;margin-top:4px">Use the upload buttons above to add clinical photos</p>
              </div>
              @endforelse
            </div>
          </div>
        </div>
      </div>{{-- /photos tab --}}

      {{-- ══════ PROGRESS TAB ══════ --}}
      <div x-show="activeTab === 'progress'" x-cloak>
        <div class="form-section">
          <div class="form-section-header"><h3>Progress Tracking</h3></div>
          <div class="form-body">
            @php
              // Collect scale history from past visits
              $scaleHistory = [];
              foreach (($visitHistory ?? collect())->reverse() as $histVisit) {
                $visitDate = $histVisit->created_at->format('d M');
                foreach ($histVisit->scales ?? [] as $scale) {
                  if (!isset($scaleHistory[$scale->scale_name])) {
                    $scaleHistory[$scale->scale_name] = ['labels' => [], 'values' => []];
                  }
                  $scaleHistory[$scale->scale_name]['labels'][] = $visitDate;
                  $scaleHistory[$scale->scale_name]['values'][] = $scale->score;
                }
              }
            @endphp

            @if(count($scaleHistory) > 0)
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px">
              @foreach($scaleHistory as $scaleName => $data)
              <div style="background:var(--bg);border-radius:10px;padding:16px">
                <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;margin-bottom:12px">{{ $scaleName }} Trend</div>
                <canvas id="chart-{{ Str::slug($scaleName) }}" height="150"></canvas>
              </div>
              @endforeach
            </div>
            
            <script>
              document.addEventListener('DOMContentLoaded', function() {
                @foreach($scaleHistory as $scaleName => $data)
                new Chart(document.getElementById('chart-{{ Str::slug($scaleName) }}').getContext('2d'), {
                  type: 'line',
                  data: {
                    labels: {!! json_encode($data['labels']) !!},
                    datasets: [{
                      label: '{{ $scaleName }}',
                      data: {!! json_encode($data['values']) !!},
                      borderColor: 'rgb(79, 70, 229)',
                      backgroundColor: 'rgba(79, 70, 229, 0.1)',
                      tension: 0.3,
                      fill: true,
                    }]
                  },
                  options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                  }
                });
                @endforeach
              });
            </script>
            @else
            <div style="padding:40px;text-align:center;color:var(--text3)">
              <div style="font-size:32px;margin-bottom:12px">📈</div>
              <div style="font-size:14px;font-weight:600;margin-bottom:4px">Progress Charts</div>
              <div style="font-size:13px">Add grading scales in previous visits to see progress trends</div>
            </div>
            @endif

            {{-- Visit Summary Table --}}
            @if(($visitHistory ?? collect())->count() > 1)
            <div style="margin-top:20px">
              <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;margin-bottom:10px">Visit Summary</div>
              <table style="width:100%;border-collapse:collapse">
                <thead>
                  <tr style="background:var(--bg)">
                    <th style="padding:8px 10px;text-align:left;font-size:11px;color:var(--text3)">Date</th>
                    <th style="padding:8px 10px;text-align:left;font-size:11px;color:var(--text3)">Chief Complaint</th>
                    <th style="padding:8px 10px;text-align:left;font-size:11px;color:var(--text3)">Diagnosis</th>
                    <th style="padding:8px 10px;text-align:left;font-size:11px;color:var(--text3)">Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($visitHistory as $histVisit)
                  <tr style="border-bottom:1px solid var(--border){{ $histVisit->id === $visit->id ? ';background:var(--blue-light)' : '' }}">
                    <td style="padding:10px;font-size:12px;color:var(--dark)">{{ $histVisit->created_at->format('d M Y') }}</td>
                    <td style="padding:10px;font-size:12px;color:var(--text2)">{{ $histVisit->chief_complaint ?? 'N/A' }}</td>
                    <td style="padding:10px;font-size:12px;color:var(--text2)">{{ Str::limit($histVisit->diagnosis_text ?? 'N/A', 30) }}</td>
                    <td style="padding:10px">
                      <span style="font-size:10px;font-weight:600;padding:3px 8px;border-radius:100px;
                        @if($histVisit->status === 'finalised') background:var(--green-light);color:var(--green)
                        @elseif($histVisit->status === 'draft') background:#fffbeb;color:var(--amber)
                        @else background:#f1f5f9;color:#64748b @endif">
                        {{ ucfirst(str_replace('_', ' ', $histVisit->status)) }}
                      </span>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            @endif
          </div>
        </div>
      </div>

      {{-- ══════ INVESTIGATIONS TAB ══════ --}}
      <div x-show="activeTab === 'investigations'" x-cloak>
        <div class="form-section">
          <div class="form-section-header">
            <h3>Lab Investigations</h3>
            <a href="{{ route('vendor.index') }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;background:var(--blue);color:white;margin-left:auto;text-decoration:none">+ Order Lab Tests</a>
          </div>
          <div class="form-body">
            @php
              $pendingLabs = ($labOrders ?? collect())->where('status', '!=', 'completed');
              $completedLabs = ($labOrders ?? collect())->where('status', 'completed');
            @endphp
            
            <div style="background:var(--bg);border-radius:10px;padding:16px;margin-bottom:12px">
              <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">Pending Tests</div>
              <div style="display:flex;flex-direction:column;gap:8px">
                @forelse($pendingLabs as $lab)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:white;border-radius:8px;border:1px solid var(--border)">
                  <div>
                    <div style="font-size:13px;font-weight:600;color:var(--dark)">
                      {{ $lab->tests->pluck('test_name')->join(', ') ?: 'Lab Tests' }}
                    </div>
                    <div style="font-size:11px;color:var(--text3)">
                      Ordered: {{ $lab->created_at->format('d M Y') }}
                      @if($lab->vendor) · {{ $lab->vendor->name }} @endif
                    </div>
                  </div>
                  <span style="background:#fffbeb;color:var(--amber);padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600">
                    {{ ucfirst($lab->status) }}
                  </span>
                </div>
                @empty
                <div style="text-align:center;padding:16px;color:var(--text3);font-size:12px">
                  No pending lab tests
                </div>
                @endforelse
              </div>
            </div>
            
            <div style="background:var(--bg);border-radius:10px;padding:16px">
              <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">Previous Results</div>
              <div style="display:flex;flex-direction:column;gap:8px">
                @forelse($completedLabs as $lab)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:white;border-radius:8px;border:1px solid var(--border)">
                  <div>
                    <div style="font-size:13px;font-weight:600;color:var(--dark)">
                      {{ $lab->tests->pluck('test_name')->join(', ') ?: 'Lab Tests' }}
                    </div>
                    <div style="font-size:11px;color:var(--text3)">
                      Completed: {{ $lab->updated_at->format('d M Y') }}
                    </div>
                  </div>
                  <span style="background:var(--green-light);color:var(--green);padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600">
                    Completed ✓
                  </span>
                </div>
                @empty
                <div style="text-align:center;padding:16px;color:var(--text3);font-size:12px">
                  No previous lab results
                </div>
                @endforelse
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ══════ BILLING TAB ══════ --}}
      <div x-show="activeTab === 'billing'" x-cloak>
        <div class="form-section">
          <div class="form-section-header">
            <h3>Visit Billing</h3>
            @if(!$visit->invoice)
            <a href="{{ route('billing.create') }}?patient_id={{ $patient->id }}&visit_id={{ $visit->id }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;background:var(--blue);color:white;text-decoration:none;margin-left:auto">+ Create Invoice</a>
            @endif
          </div>
          <div class="form-body">
            @if($visit->invoice)
              @php $invoice = $visit->invoice; @endphp
              <div style="background:var(--green-light);border:1px solid rgba(5,150,105,.15);border-radius:10px;padding:16px;margin-bottom:16px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                  <div>
                    <div style="font-size:14px;font-weight:700;color:var(--dark)">Invoice #{{ $invoice->invoice_number }}</div>
                    <div style="font-size:12px;color:var(--text3)">Created: {{ $invoice->created_at->format('d M Y') }}</div>
                  </div>
                  <div style="display:flex;gap:8px">
                    <a href="{{ route('billing.show', $invoice) }}" style="padding:6px 12px;background:var(--blue);color:white;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none">View Invoice</a>
                    <a href="{{ route('billing.pdf', $invoice) }}" style="padding:6px 12px;background:white;color:var(--text2);border:1px solid var(--border);border-radius:6px;font-size:12px;font-weight:600;text-decoration:none">Download PDF</a>
                  </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
                  <div>
                    <div style="font-size:11px;color:var(--text3);text-transform:uppercase">Subtotal</div>
                    <div style="font-size:16px;font-weight:700;color:var(--dark)">₹{{ number_format($invoice->subtotal ?? 0, 2) }}</div>
                  </div>
                  <div>
                    <div style="font-size:11px;color:var(--text3);text-transform:uppercase">GST</div>
                    <div style="font-size:16px;font-weight:700;color:var(--dark)">₹{{ number_format(($invoice->cgst ?? 0) + ($invoice->sgst ?? 0), 2) }}</div>
                  </div>
                  <div>
                    <div style="font-size:11px;color:var(--text3);text-transform:uppercase">Total</div>
                    <div style="font-size:16px;font-weight:700;color:var(--blue)">₹{{ number_format($invoice->total ?? 0, 2) }}</div>
                  </div>
                  <div>
                    <div style="font-size:11px;color:var(--text3);text-transform:uppercase">Status</div>
                    <div style="font-size:14px;font-weight:700;color:{{ $invoice->payment_status === 'paid' ? 'var(--green)' : 'var(--amber)' }}">
                      {{ ucfirst($invoice->payment_status ?? 'pending') }}
                    </div>
                  </div>
                </div>
              </div>
              
              @if($invoice->items && $invoice->items->count() > 0)
              <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">Invoice Items</div>
              <table style="width:100%;border-collapse:collapse">
                <thead>
                  <tr style="background:var(--bg)">
                    <th style="padding:8px 10px;text-align:left;font-size:11px;color:var(--text3)">Description</th>
                    <th style="padding:8px 10px;text-align:center;font-size:11px;color:var(--text3)">Qty</th>
                    <th style="padding:8px 10px;text-align:right;font-size:11px;color:var(--text3)">Rate</th>
                    <th style="padding:8px 10px;text-align:right;font-size:11px;color:var(--text3)">Total</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($invoice->items as $item)
                  <tr style="border-bottom:1px solid var(--border)">
                    <td style="padding:10px;font-size:13px;color:var(--dark)">{{ $item->description }}</td>
                    <td style="padding:10px;font-size:13px;color:var(--text2);text-align:center">{{ $item->quantity }}</td>
                    <td style="padding:10px;font-size:13px;color:var(--text2);text-align:right">₹{{ number_format($item->unit_price, 2) }}</td>
                    <td style="padding:10px;font-size:13px;font-weight:600;color:var(--dark);text-align:right">₹{{ number_format($item->total, 2) }}</td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
              @endif
            @else
            <div style="background:var(--blue-light);border:1px solid rgba(20,71,230,.15);border-radius:10px;padding:16px;text-align:center">
              <div style="font-size:32px;margin-bottom:8px">🧾</div>
              <p style="font-size:13px;font-weight:600;color:var(--dark);margin-bottom:4px">No invoice for this visit yet</p>
              <p style="font-size:12px;color:var(--text3)">Click "Create Invoice" to generate a GST-compliant invoice</p>
            </div>
            @endif
          </div>
        </div>
      </div>

    </div>{{-- /emr-main --}}
  </div>{{-- /emr-body --}}

  {{-- STICKY BOTTOM SAVE BAR --}}
  <div class="bottom-bar">
    <div class="save-info">
      <template x-if="autoSaved">
        <span><strong>Auto-saved</strong> · <span x-text="lastSaved"></span></span>
      </template>
      <template x-if="!autoSaved">
        <span style="color:var(--amber)">Saving…</span>
      </template>
    </div>
    <div style="margin-left:auto;display:flex;gap:10px">
      <button type="button" onclick="saveAsDraft()" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:transparent;color:var(--text2)">Save Draft</button>
      <button type="button" onclick="window.print()" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:transparent;color:var(--text2)">🖨 Print Note</button>
      @if($visit->status !== 'finalised')
      <button type="button" onclick="finaliseVisit()" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;background:var(--blue);color:white">✓ Finalise &amp; Send Prescription via WhatsApp</button>
      @else
      <span style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;background:var(--green-light);color:var(--green)">✓ Visit Completed</span>
      @endif
    </div>
  </div>

</form>
</div>{{-- /x-data wrapper --}}
@endsection

@push('scripts')
<script>
  // Global triggerAutoSave function for Alpine components
  window.triggerAutoSave = function() {
    // Dispatch event to trigger auto-save
    window.dispatchEvent(new CustomEvent('emr-autosave'));
    
    // Also update Alpine state if available
    const wrapper = document.querySelector('[x-data]');
    if (wrapper && wrapper.__x) {
      wrapper.__x.$data.autoSaved = false;
      setTimeout(() => {
        wrapper.__x.$data.autoSaved = true;
        wrapper.__x.$data.lastSaved = new Date().toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'});
      }, 1200);
    }
  };
  
  // Auto-save via AJAX on field changes
  document.addEventListener('DOMContentLoaded', function () {
    let saveTimer;
    const form = document.getElementById('emr-form');
    const saveUrl = '{{ route('emr.update', [$patient, $visit]) }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    
    function performAutoSave() {
      const formData = new FormData(form);
      const data = {};
      
      // Extract relevant fields
      ['chief_complaint', 'history', 'diagnosis_text', 'diagnosis_code', 'plan', 'followup_in_days', 'followup_date'].forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input) data[field] = input.value;
      });
      
      // Extract structured data
      const structuredData = {};
      ['duration', 'onset', 'progression', 'previous_treatment'].forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input && input.value) structuredData[field] = input.value;
      });
      data.structured_data = structuredData;
      
      console.log('Auto-saving EMR...', data);
      
      fetch(saveUrl, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify(data)
      })
      .then(res => res.json())
      .then(result => {
        if (result.saved) {
          console.log('Auto-saved at', result.at);
        }
      })
      .catch(err => console.error('Auto-save failed:', err));
    }
    
    form?.addEventListener('input', function () {
      clearTimeout(saveTimer);
      saveTimer = setTimeout(performAutoSave, 2000);
    });
    
    // Listen for custom auto-save events
    window.addEventListener('emr-autosave', function() {
      clearTimeout(saveTimer);
      saveTimer = setTimeout(performAutoSave, 2000);
    });
  });
  
  // Update follow-up date based on selected interval
  function updateFollowupDate(days) {
    if (days && parseInt(days) > 0) {
      const date = new Date();
      date.setDate(date.getDate() + parseInt(days));
      const formatted = date.toISOString().split('T')[0];
      document.getElementById('followup_date').value = formatted;
    }
  }
  
  // Lesion management
  function openAddLesionModal() {
    // For now, show a simple prompt - can be enhanced with a modal
    const region = prompt('Enter body region (e.g., Left Cheek, Forehead):');
    if (!region) return;
    
    const type = prompt('Enter lesion type (e.g., Plaque, Papule, Macule):');
    if (!type) return;
    
    const description = prompt('Enter description (optional):') || '';
    
    addLesion(region, type, description);
  }
  
  function addLesion(region, type, description) {
    const url = '{{ route('emr.add-lesion', [$patient, $visit]) }}';
    
    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        body_region: region,
        lesion_type: type,
        description: description
      })
    })
    .then(res => res.json())
    .then(result => {
      if (result.success) {
        location.reload();
      } else {
        alert('Failed to add lesion: ' + (result.error || 'Unknown error'));
      }
    })
    .catch(err => {
      console.error('Add lesion error:', err);
      alert('Failed to add lesion');
    });
  }
  
  function removeLesion(lesionId) {
    if (!confirm('Remove this lesion annotation?')) return;
    
    fetch(`{{ url('emr/' . $patient->id . '/' . $visit->id . '/lesions') }}/${lesionId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      }
    })
    .then(res => res.json())
    .then(result => {
      if (result.success) {
        document.querySelector(`[data-lesion-id="${lesionId}"]`)?.remove();
      }
    })
    .catch(err => console.error('Remove lesion error:', err));
  }

  // Save draft manually
  function saveAsDraft() {
    const form = document.getElementById('emr-form');
    const saveUrl = '{{ route('emr.update', [$patient, $visit]) }}';
    const data = {};
    
    // Extract relevant fields
    ['chief_complaint', 'history', 'diagnosis_text', 'diagnosis_code', 'plan', 'followup_in_days', 'followup_date'].forEach(field => {
      const input = form.querySelector(`[name="${field}"]`);
      if (input) data[field] = input.value;
    });
    
    // Extract structured data
    const structuredData = {};
    ['duration', 'onset', 'progression', 'previous_treatment'].forEach(field => {
      const input = form.querySelector(`[name="${field}"]`);
      if (input && input.value) structuredData[field] = input.value;
    });
    data.structured_data = structuredData;
    
    fetch(saveUrl, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
      if (result.saved) {
        alert('Draft saved successfully!');
      } else {
        alert('Failed to save draft');
      }
    })
    .catch(err => {
      console.error('Save error:', err);
      alert('Failed to save draft');
    });
  }

  // Finalise visit
  function finaliseVisit() {
    if (!confirm('Are you sure you want to finalise this visit? This will mark the consultation as complete and send the prescription via WhatsApp.')) {
      return;
    }
    
    // Create a form and submit it
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('emr.finalise', [$patient, $visit]) }}';
    
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);
    
    document.body.appendChild(form);
    form.submit();
  }
</script>
@endpush
