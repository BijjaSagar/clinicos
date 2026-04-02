<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Discharge Summary — {{ $admission->admission_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        .page { padding: 20px 28px; }
        .header { border-bottom: 2px solid #1447E6; padding-bottom: 12px; margin-bottom: 16px; }
        .clinic-name { font-size: 20px; font-weight: 700; color: #1447E6; }
        .doc-title { font-size: 14px; font-weight: 700; text-align: center; margin: 14px 0 10px; color: #111; letter-spacing: 0.5px; text-transform: uppercase; }
        .section { margin-bottom: 14px; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #1447E6; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin-bottom: 8px; }
        .grid2 { display: table; width: 100%; }
        .col { display: table-cell; width: 50%; padding-right: 12px; }
        .field { margin-bottom: 6px; }
        .label { color: #6b7280; font-size: 9px; text-transform: uppercase; letter-spacing: 0.3px; }
        .value { font-weight: 500; margin-top: 1px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #f3f4f6; text-align: left; padding: 5px 7px; font-weight: 600; border: 1px solid #e5e7eb; }
        td { padding: 5px 7px; border: 1px solid #e5e7eb; vertical-align: top; }
        .badge-critical { color: #dc2626; font-weight: 700; }
        .footer-text { font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 10px; margin-top: 16px; }
        .sign-area { display: table; width: 100%; margin-top: 28px; }
        .sign-col { display: table-cell; width: 50%; }
        .sign-line { border-top: 1px solid #374151; width: 140px; margin-top: 36px; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="clinic-name">{{ $clinic?->name ?? 'ClinicOS' }}</div>
        @if($clinic?->address)
        <div style="font-size:10px;color:#6b7280;margin-top:2px;">{{ $clinic->address }}</div>
        @endif
    </div>

    <div class="doc-title">Discharge Summary</div>

    {{-- Patient Info --}}
    <div class="section">
        <div class="section-title">Patient Information</div>
        <div class="grid2">
            <div class="col">
                <div class="field"><div class="label">Patient Name</div><div class="value">{{ $admission->patient->name }}</div></div>
                <div class="field"><div class="label">Date of Birth</div><div class="value">{{ $admission->patient->date_of_birth ? \Carbon\Carbon::parse($admission->patient->date_of_birth)->format('d M Y') : '—' }}</div></div>
                <div class="field"><div class="label">Gender</div><div class="value">{{ ucfirst($admission->patient->gender ?? '—') }}</div></div>
            </div>
            <div class="col">
                <div class="field"><div class="label">Admission Number</div><div class="value">{{ $admission->admission_number }}</div></div>
                <div class="field"><div class="label">Ward / Bed</div><div class="value">{{ $admission->ward?->name ?? '—' }} / {{ $admission->bed?->bed_number ?? '—' }}</div></div>
                <div class="field"><div class="label">Treating Doctor</div><div class="value">Dr. {{ $admission->primaryDoctor?->name ?? '—' }}</div></div>
            </div>
        </div>
    </div>

    {{-- Admission / Discharge Dates --}}
    <div class="section">
        <div class="section-title">Admission Details</div>
        <div class="grid2">
            <div class="col">
                <div class="field"><div class="label">Date of Admission</div><div class="value">{{ \Carbon\Carbon::parse($admission->admission_date)->format('d M Y, h:i A') }}</div></div>
                <div class="field"><div class="label">Admission Type</div><div class="value">{{ ucfirst(str_replace('_', ' ', $admission->admission_type)) }}</div></div>
            </div>
            <div class="col">
                <div class="field"><div class="label">Date of Discharge</div><div class="value">{{ $admission->discharge_date ? \Carbon\Carbon::parse($admission->discharge_date)->format('d M Y, h:i A') : '—' }}</div></div>
                <div class="field"><div class="label">Discharge Type</div><div class="value">{{ $admission->discharge_type ? ucfirst(str_replace('_', ' ', $admission->discharge_type)) : '—' }}</div></div>
            </div>
        </div>
        <div class="field" style="margin-top:6px;">
            <div class="label">Diagnosis at Admission</div>
            <div class="value">{{ $admission->diagnosis_at_admission }}</div>
        </div>
        @if($admission->final_diagnosis)
        <div class="field" style="margin-top:6px;">
            <div class="label">Final Diagnosis / Discharge Diagnosis</div>
            <div class="value" style="font-weight:700;">{{ $admission->final_diagnosis }}</div>
        </div>
        @endif
    </div>

    {{-- Vitals Summary --}}
    @if($vitals->isNotEmpty())
    <div class="section">
        <div class="section-title">Vitals Summary ({{ $vitals->count() }} recordings)</div>
        <table>
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Temp (°C)</th>
                    <th>Pulse</th>
                    <th>BP</th>
                    <th>SpO₂</th>
                    <th>RR</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vitals->take(10) as $v)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($v->recorded_at)->format('d/m h:i A') }}</td>
                    <td>{{ $v->temperature ?? '—' }}</td>
                    <td>{{ $v->pulse ?? '—' }}</td>
                    <td>{{ $v->bp_systolic && $v->bp_diastolic ? "{$v->bp_systolic}/{$v->bp_diastolic}" : '—' }}</td>
                    <td>{{ $v->spo2 ? $v->spo2.'%' : '—' }}</td>
                    <td>{{ $v->respiratory_rate ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Medications --}}
    @if($medicationOrders->isNotEmpty())
    <div class="section">
        <div class="section-title">Medication Orders</div>
        <table>
            <thead>
                <tr><th>Medicine</th><th>Dose</th><th>Route</th><th>Frequency</th><th>Duration</th></tr>
            </thead>
            <tbody>
                @foreach($medicationOrders as $med)
                <tr>
                    <td>{{ $med->medicine_name }}</td>
                    <td>{{ $med->dose ?? '—' }}</td>
                    <td>{{ $med->route ?? '—' }}</td>
                    <td>{{ $med->frequency ?? '—' }}</td>
                    <td>{{ $med->duration ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Progress Notes Summary --}}
    @if($progressNotes->isNotEmpty())
    <div class="section">
        <div class="section-title">Clinical Notes Summary ({{ $progressNotes->count() }} entries)</div>
        @foreach($progressNotes->take(5) as $note)
        <div style="margin-bottom:8px;padding:6px;background:#f9fafb;border-left:3px solid #1447E6;">
            <div style="font-size:9px;color:#6b7280;margin-bottom:3px;">
                {{ \Carbon\Carbon::parse($note->note_date)->format('d M Y') }} — {{ $note->author?->name ?? 'Staff' }}
            </div>
            <div><strong>A:</strong> {{ $note->assessment }}</div>
            <div><strong>P:</strong> {{ $note->plan }}</div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Discharge Notes --}}
    @if($admission->discharge_notes)
    <div class="section">
        <div class="section-title">Discharge Instructions &amp; Notes</div>
        <div style="background:#fffbeb;border:1px solid #fcd34d;padding:8px;border-radius:4px;">{{ $admission->discharge_notes }}</div>
    </div>
    @endif

    {{-- Signature --}}
    <div class="sign-area">
        <div class="sign-col">
            <div class="sign-line"></div>
            <div style="font-size:10px;margin-top:4px;">Dr. {{ $admission->primaryDoctor?->name ?? '—' }}</div>
            <div style="font-size:9px;color:#6b7280;">Treating Physician</div>
        </div>
        <div class="sign-col" style="text-align:right;">
            <div style="margin-top:36px;font-size:10px;">Generated: {{ now()->format('d M Y, h:i A') }}</div>
        </div>
    </div>

    {{-- Footer --}}
    @if($footer)
    <div class="footer-text">{{ $footer }}</div>
    @endif

</div>
</body>
</html>
