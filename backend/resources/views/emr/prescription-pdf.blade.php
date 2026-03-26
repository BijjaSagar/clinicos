<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription - {{ $patient->name ?? 'Patient' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1a1a1a;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #1447e6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .clinic-name {
            font-size: 20px;
            font-weight: bold;
            color: #1447e6;
        }
        .clinic-info {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .doctor-info {
            text-align: right;
            font-size: 11px;
        }
        .doctor-name {
            font-weight: bold;
            color: #333;
        }
        .patient-section {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .patient-section h3 {
            font-size: 13px;
            color: #333;
            margin-bottom: 8px;
        }
        .patient-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .patient-detail {
            font-size: 11px;
        }
        .patient-detail strong {
            color: #666;
        }
        .rx-symbol {
            font-size: 24px;
            font-weight: bold;
            color: #1447e6;
            margin-bottom: 10px;
        }
        .prescription-section {
            margin-bottom: 20px;
        }
        .prescription-section h4 {
            font-size: 13px;
            color: #333;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        .drug-table {
            width: 100%;
            border-collapse: collapse;
        }
        .drug-table th {
            background: #f0f0f0;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 1px solid #ddd;
        }
        .drug-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        .drug-name {
            font-weight: bold;
            color: #333;
        }
        .drug-generic {
            font-size: 10px;
            color: #888;
        }
        .diagnosis-section {
            background: #fff8e1;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        .diagnosis-section h4 {
            font-size: 12px;
            color: #333;
            margin-bottom: 5px;
        }
        .advice-section {
            margin-bottom: 20px;
        }
        .advice-section h4 {
            font-size: 12px;
            color: #333;
            margin-bottom: 8px;
        }
        .advice-section ul {
            padding-left: 20px;
        }
        .advice-section li {
            font-size: 11px;
            margin-bottom: 4px;
            color: #555;
        }
        .followup-section {
            background: #e3f2fd;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .signature {
            text-align: right;
        }
        .signature-line {
            width: 150px;
            border-top: 1px solid #333;
            margin-left: auto;
            margin-bottom: 5px;
        }
        .disclaimer {
            font-size: 9px;
            color: #999;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <table width="100%">
            <tr>
                <td width="60%">
                    <div class="clinic-name">{{ $clinic->name ?? 'Clinic Name' }}</div>
                    <div class="clinic-info">
                        {{ $clinic->address ?? '' }}<br>
                        @if($clinic->phone ?? false)Phone: {{ $clinic->phone }}@endif
                        @if($clinic->email ?? false) | Email: {{ $clinic->email }}@endif
                        @if($clinic->gstin ?? false)<br>GSTIN: {{ $clinic->gstin }}@endif
                    </div>
                </td>
                <td width="40%" class="doctor-info">
                    <div class="doctor-name">{{ $visit->doctor->name ?? 'Doctor' }}</div>
                    <div>{{ $visit->doctor->specialty ?? '' }}</div>
                    <div>{{ $visit->doctor->qualification ?? '' }}</div>
                    @if($visit->doctor->registration_number ?? false)
                    <div>Reg. No: {{ $visit->doctor->registration_number }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Patient Info --}}
    <div class="patient-section">
        <h3>Patient Details</h3>
        <div class="patient-details">
            <div class="patient-detail"><strong>Name:</strong> {{ $patient->name }}</div>
            <div class="patient-detail"><strong>Age/Sex:</strong> {{ $patient->age_years ?? 'N/A' }} / {{ ucfirst($patient->sex ?? 'N/A') }}</div>
            <div class="patient-detail"><strong>Phone:</strong> {{ $patient->phone }}</div>
            @if($patient->blood_group)
            <div class="patient-detail"><strong>Blood Group:</strong> {{ $patient->blood_group }}</div>
            @endif
            <div class="patient-detail"><strong>Date:</strong> {{ now()->format('d M Y') }}</div>
            <div class="patient-detail"><strong>Visit #:</strong> {{ $visit->visit_number ?? 1 }}</div>
        </div>
    </div>

    {{-- Diagnosis --}}
    @if($visit->diagnosis_text)
    <div class="diagnosis-section">
        <h4>Diagnosis</h4>
        <div>{{ $visit->diagnosis_text }}</div>
        @if($visit->diagnosis_code)
        <div style="font-size: 10px; color: #888; margin-top: 5px;">ICD-10: {{ $visit->diagnosis_code }}</div>
        @endif
    </div>
    @endif

    {{-- Prescription --}}
    <div class="prescription-section">
        <div class="rx-symbol">℞</div>
        <h4>Prescription</h4>
        
        @if($visit->prescriptions && $visit->prescriptions->isNotEmpty())
            @php $prescription = $visit->prescriptions->first(); @endphp
            @if($prescription->drugs && $prescription->drugs->isNotEmpty())
            <table class="drug-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="30%">Drug</th>
                        <th width="12%">Dose</th>
                        <th width="15%">Frequency</th>
                        <th width="12%">Duration</th>
                        <th width="26%">Instructions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescription->drugs as $index => $drug)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="drug-name">{{ $drug->drug_name }}</div>
                            @if($drug->generic_name)
                            <div class="drug-generic">{{ $drug->generic_name }}</div>
                            @endif
                        </td>
                        <td>{{ $drug->dose ?? '-' }}</td>
                        <td>{{ $drug->frequency ?? '-' }}</td>
                        <td>{{ $drug->duration ?? '-' }}</td>
                        <td>{{ $drug->instructions ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p style="color: #888; font-style: italic;">No medications prescribed</p>
            @endif
        @else
        <p style="color: #888; font-style: italic;">No medications prescribed</p>
        @endif
    </div>

    {{-- Plan / Advice --}}
    @if($visit->plan)
    <div class="advice-section">
        <h4>Advice / Instructions</h4>
        <div style="font-size: 11px; color: #555;">{{ $visit->plan }}</div>
    </div>
    @endif

    {{-- Follow-up --}}
    @if($visit->followup_date)
    <div class="followup-section">
        <strong>Next Follow-up:</strong> {{ \Carbon\Carbon::parse($visit->followup_date)->format('d M Y') }}
        @if($visit->followup_in_days)
        ({{ $visit->followup_in_days }} days)
        @endif
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="signature">
            <div class="signature-line"></div>
            <div><strong>{{ $visit->doctor->name ?? 'Doctor' }}</strong></div>
            <div style="font-size: 10px; color: #666;">{{ $visit->doctor->specialty ?? '' }}</div>
        </div>
    </div>

    <div class="disclaimer">
        This prescription is electronically generated via ClinicOS. 
        Valid only with doctor's signature. Not valid for medico-legal purposes without original.
    </div>
</body>
</html>
