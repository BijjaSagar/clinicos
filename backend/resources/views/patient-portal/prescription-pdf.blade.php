<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; padding: 24px; }
.header { border-bottom: 2px solid #1447e6; padding-bottom: 12px; margin-bottom: 16px; }
.clinic-name { font-size: 18px; font-weight: bold; color: #1447e6; }
.section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin: 16px 0 8px; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
.info-item label { font-size: 9px; color: #9ca3af; display: block; }
.info-item span { font-size: 11px; font-weight: 600; }
table { width: 100%; border-collapse: collapse; font-size: 10px; }
th { background: #eff6ff; color: #1e40af; font-size: 9px; text-transform: uppercase; padding: 6px 8px; text-align: left; }
td { padding: 6px 8px; border-bottom: 1px solid #f3f4f6; }
.footer { margin-top: 32px; border-top: 1px solid #e5e7eb; padding-top: 12px; font-size: 9px; color: #9ca3af; }
</style>
</head>
<body>
<div class="header">
    <div class="clinic-name">{{ $clinic->name ?? 'Clinic' }}</div>
    <div style="font-size:9px;color:#6b7280;margin-top:2px;">{{ $clinic->address ?? '' }}</div>
</div>

<div style="font-size:14px;font-weight:bold;margin-bottom:12px;">Prescription</div>

<div class="info-grid">
    <div class="info-item"><label>Patient</label><span>{{ $patient->name }}</span></div>
    <div class="info-item"><label>Date</label><span>{{ \Carbon\Carbon::parse($visit->visit_date)->format('d M Y') }}</span></div>
    <div class="info-item"><label>Phone</label><span>{{ $patient->phone ?? '—' }}</span></div>
    <div class="info-item"><label>Age / Sex</label><span>{{ $patient->age_years ?? '—' }} yrs / {{ ucfirst($patient->sex ?? '—') }}</span></div>
</div>

@if($drugs->isNotEmpty())
<div class="section-title">Medications</div>
<table>
    <thead>
        <tr>
            <th>Medicine</th>
            <th>Dose</th>
            <th>Frequency</th>
            <th>Duration</th>
            <th>Instructions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($drugs as $drug)
        <tr>
            <td style="font-weight:600;">{{ $drug->medicine_name ?? $drug->drug_name ?? '—' }}</td>
            <td>{{ $drug->dosage ?? '—' }}</td>
            <td>{{ $drug->frequency ?? '—' }}</td>
            <td>{{ $drug->duration ?? '—' }}</td>
            <td>{{ $drug->instructions ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="color:#9ca3af;font-size:10px;">No medications prescribed.</p>
@endif

<div class="footer">
    Generated on {{ now()->format('d M Y, h:i A') }} via Patient Portal &nbsp;|&nbsp; {{ $clinic->name ?? '' }}
</div>
</body>
</html>
