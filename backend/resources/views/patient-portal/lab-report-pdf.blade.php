<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; padding: 24px; }
.header { border-bottom: 2px solid #059669; padding-bottom: 12px; margin-bottom: 16px; }
.clinic-name { font-size: 18px; font-weight: bold; color: #059669; }
.section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin: 16px 0 8px; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
.info-item label { font-size: 9px; color: #9ca3af; display: block; }
.info-item span { font-size: 11px; font-weight: 600; }
table { width: 100%; border-collapse: collapse; font-size: 10px; }
th { background: #ecfdf5; color: #065f46; font-size: 9px; text-transform: uppercase; padding: 6px 8px; text-align: left; }
td { padding: 6px 8px; border-bottom: 1px solid #f3f4f6; }
.critical { color: #dc2626; font-weight: bold; }
.footer { margin-top: 32px; border-top: 1px solid #e5e7eb; padding-top: 12px; font-size: 9px; color: #9ca3af; }
</style>
</head>
<body>
<div class="header">
    <div class="clinic-name">{{ $clinic->name ?? 'Clinic' }}</div>
    <div style="font-size:9px;color:#6b7280;margin-top:2px;">{{ $clinic->address ?? '' }}</div>
</div>

<div style="font-size:14px;font-weight:bold;margin-bottom:12px;">Laboratory Report</div>

<div class="info-grid">
    <div class="info-item"><label>Patient</label><span>{{ $patient->name }}</span></div>
    <div class="info-item"><label>Test</label><span>{{ $order->test_name ?? 'Lab Test' }}</span></div>
    <div class="info-item"><label>Phone</label><span>{{ $patient->phone ?? '—' }}</span></div>
    <div class="info-item"><label>Date</label><span>{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}</span></div>
</div>

@if($results->isNotEmpty())
<div class="section-title">Results</div>
<table>
    <thead>
        <tr>
            <th>Parameter</th>
            <th>Value</th>
            <th>Unit</th>
            <th>Reference Range</th>
            <th>Flag</th>
        </tr>
    </thead>
    <tbody>
        @foreach($results as $r)
        <tr>
            <td>{{ $r->parameter_name ?? '—' }}</td>
            <td class="{{ ($r->is_critical ?? false) ? 'critical' : '' }}">{{ $r->value ?? '—' }}</td>
            <td>{{ $r->unit ?? '—' }}</td>
            <td>{{ $r->reference_range ?? '—' }}</td>
            <td class="{{ ($r->is_critical ?? false) ? 'critical' : '' }}">
                {{ ($r->is_critical ?? false) ? 'CRITICAL' : (($r->flag ?? '') ?: '—') }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="color:#9ca3af;font-size:10px;">No result parameters found.</p>
@endif

<div class="footer">
    Generated on {{ now()->format('d M Y, h:i A') }} via Patient Portal &nbsp;|&nbsp; {{ $clinic->name ?? '' }}
</div>
</body>
</html>
