<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Prescription - {{ $patient->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1f2937;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #1447e6;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .clinic-name {
            font-size: 18px;
            font-weight: bold;
            color: #1447e6;
        }
        .clinic-address {
            font-size: 9px;
            color: #6b7280;
            margin-top: 3px;
        }
        .doctor-info {
            margin-top: 8px;
            padding: 8px;
            background: #f0f4ff;
            border-radius: 4px;
        }
        .doctor-name {
            font-weight: bold;
            color: #1f2937;
        }
        .doctor-reg {
            font-size: 9px;
            color: #6b7280;
        }
        .patient-section {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .patient-row {
            display: table-row;
        }
        .patient-cell {
            display: table-cell;
            padding: 3px 10px;
        }
        .patient-label {
            font-weight: bold;
            color: #6b7280;
            font-size: 9px;
            text-transform: uppercase;
        }
        .patient-value {
            color: #1f2937;
        }
        .rx-symbol {
            font-size: 24px;
            font-weight: bold;
            color: #1447e6;
            margin: 15px 0 10px 0;
        }
        .prescription-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .prescription-table th {
            background: #1447e6;
            color: white;
            padding: 8px 10px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }
        .prescription-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .prescription-table tr:nth-child(even) {
            background: #f9fafb;
        }
        .drug-name {
            font-weight: bold;
            color: #1f2937;
        }
        .drug-form {
            font-size: 9px;
            color: #6b7280;
        }
        .dosage {
            color: #1447e6;
            font-weight: 600;
        }
        .frequency {
            font-weight: 500;
        }
        .instructions {
            font-size: 9px;
            color: #6b7280;
            font-style: italic;
            margin-top: 3px;
        }
        .controlled-badge {
            display: inline-block;
            background: #fef2f2;
            color: #dc2626;
            font-size: 8px;
            padding: 2px 5px;
            border-radius: 3px;
            margin-left: 5px;
        }
        .substitutable {
            font-size: 8px;
            color: #6b7280;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        .signature-area {
            text-align: right;
            margin-top: 30px;
        }
        .signature-line {
            width: 180px;
            border-top: 1px solid #1f2937;
            margin-left: auto;
            padding-top: 5px;
            text-align: center;
        }
        .advice-section {
            margin-top: 15px;
            padding: 10px;
            background: #fef3cd;
            border-radius: 4px;
        }
        .advice-title {
            font-weight: bold;
            color: #856404;
            font-size: 10px;
            margin-bottom: 5px;
        }
        .advice-text {
            color: #856404;
            font-size: 9px;
        }
        .diagnosis-section {
            margin: 10px 0;
            padding: 8px;
            background: #e8f5e9;
            border-left: 3px solid #4caf50;
        }
        .diagnosis-label {
            font-size: 9px;
            color: #2e7d32;
            font-weight: bold;
        }
        .diagnosis-text {
            color: #1f2937;
            font-weight: 500;
        }
        .date-section {
            text-align: right;
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 10px;
        }
        .watermark {
            position: fixed;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 8px;
            color: #d1d5db;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="clinic-name">{{ $clinic->name }}</div>
        <div class="clinic-address">
            {{ $clinic->address ?? '' }}<br>
            Phone: {{ $clinic->phone ?? 'N/A' }} | Email: {{ $clinic->email ?? 'N/A' }}
            @if($clinic->gstin)
            | GSTIN: {{ $clinic->gstin }}
            @endif
        </div>
        <div class="doctor-info">
            <div class="doctor-name">{{ $doctor->name }}</div>
            <div class="doctor-reg">
                {{ $doctor->specialty ?? 'General Practitioner' }}
                @if($doctor->registration_number)
                | Reg. No: {{ $doctor->registration_number }}
                @endif
            </div>
        </div>
    </div>

    {{-- Date --}}
    <div class="date-section">
        Date: {{ now()->format('d M Y') }} | Time: {{ now()->format('h:i A') }}
    </div>

    {{-- Patient Info --}}
    <div class="patient-section">
        <div class="patient-row">
            <div class="patient-cell">
                <div class="patient-label">Patient Name</div>
                <div class="patient-value">{{ $patient->name }}</div>
            </div>
            <div class="patient-cell">
                <div class="patient-label">Age / Gender</div>
                <div class="patient-value">{{ $patient->age ?? 'N/A' }} yrs / {{ ucfirst($patient->gender ?? 'N/A') }}</div>
            </div>
            <div class="patient-cell">
                <div class="patient-label">Patient ID</div>
                <div class="patient-value">{{ $patient->patient_id }}</div>
            </div>
        </div>
        <div class="patient-row">
            <div class="patient-cell">
                <div class="patient-label">Phone</div>
                <div class="patient-value">{{ $patient->phone ?? 'N/A' }}</div>
            </div>
            @if($patient->abha_id)
            <div class="patient-cell">
                <div class="patient-label">ABHA ID</div>
                <div class="patient-value">{{ $patient->abha_id }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Diagnosis --}}
    @if($visit->diagnosis_text)
    <div class="diagnosis-section">
        <span class="diagnosis-label">DIAGNOSIS: </span>
        <span class="diagnosis-text">{{ $visit->diagnosis_text }}</span>
        @if($visit->diagnosis_code)
        <span style="font-size:9px; color:#666;"> ({{ $visit->diagnosis_code }})</span>
        @endif
    </div>
    @endif

    {{-- Rx Symbol --}}
    <div class="rx-symbol">℞</div>

    {{-- Prescription Table --}}
    @if($items->count() > 0)
    <table class="prescription-table">
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:35%">Medicine</th>
                <th style="width:15%">Dosage</th>
                <th style="width:20%">Frequency</th>
                <th style="width:15%">Duration</th>
                <th style="width:10%">Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <div class="drug-name">
                        {{ $item->drug_name }}
                        @if($item->drug && $item->drug->is_controlled)
                        <span class="controlled-badge">Controlled</span>
                        @endif
                    </div>
                    @if($item->drug)
                    <div class="drug-form">{{ $item->drug->form }} - {{ $item->drug->strength }}</div>
                    @endif
                    @if($item->instructions)
                    <div class="instructions">{{ $item->instructions }}</div>
                    @endif
                    @if($item->is_substitutable)
                    <div class="substitutable">Generic substitution allowed</div>
                    @endif
                </td>
                <td class="dosage">{{ $item->dosage }}</td>
                <td class="frequency">{{ $item->frequency_label }}</td>
                <td>{{ $item->duration }}</td>
                <td>{{ $item->quantity ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color:#6b7280; text-align:center; padding:20px;">No medications prescribed</p>
    @endif

    {{-- Advice / Follow-up --}}
    @if($visit->plan || $visit->followup_date)
    <div class="advice-section">
        <div class="advice-title">ADVICE / FOLLOW-UP</div>
        <div class="advice-text">
            @if($visit->plan)
            {{ $visit->plan }}
            @endif
            @if($visit->followup_date)
            <br>Follow-up on: {{ $visit->followup_date->format('d M Y') }}
            @elseif($visit->followup_in_days)
            <br>Follow-up after: {{ $visit->followup_in_days }} days
            @endif
        </div>
    </div>
    @endif

    {{-- Signature --}}
    <div class="signature-area">
        <div class="signature-line">
            Dr. {{ $doctor->name }}<br>
            <span style="font-size:8px;">{{ $doctor->specialty ?? '' }}</span>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div style="font-size:8px; color:#9ca3af; text-align:center;">
            This is a computer-generated prescription. Valid for 30 days from date of issue.
            <br>For queries, contact: {{ $clinic->phone ?? '' }}
        </div>
    </div>

    <div class="watermark">
        Generated by ClinicOS | {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
