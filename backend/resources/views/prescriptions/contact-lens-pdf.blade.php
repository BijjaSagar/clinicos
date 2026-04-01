<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contact lens prescription</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 8px; }
        .muted { color: #555; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: center; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <div style="margin-bottom: 16px;">
        <h1>Contact lens prescription</h1>
        <div><strong>{{ $clinic->name ?? 'Clinic' }}</strong></div>
        <div class="muted">Generated {{ now()->format('d M Y, h:i A') }}</div>
    </div>
    <p><strong>Patient:</strong> {{ $patient->name }} @if($patient->phone) &nbsp;|&nbsp; {{ $patient->phone }} @endif</p>
    <p><strong>Prescriber:</strong> {{ $doctor->name ?? '—' }}</p>
    <p class="muted">Visit #{{ $visit->visit_number }} &nbsp;|&nbsp; Source: {{ $contactLens['source'] ?? '—' }}</p>

    @php
        $od = $contactLens['od'] ?? [];
        $os = $contactLens['os'] ?? [];
    @endphp

    <table>
        <thead>
            <tr>
                <th>Eye</th>
                <th>BC / K</th>
                <th>Power</th>
                <th>Dia</th>
                <th>Cyl</th>
                <th>Axis</th>
                <th>Brand / Type</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>OD</td>
                <td>{{ $od['bc'] ?? $od['base_curve'] ?? '—' }}</td>
                <td>{{ $od['power'] ?? '—' }}</td>
                <td>{{ $od['dia'] ?? $od['diameter'] ?? '—' }}</td>
                <td>{{ $od['cyl'] ?? '—' }}</td>
                <td>{{ $od['axis'] ?? '—' }}</td>
                <td>{{ $od['brand'] ?? '—' }}</td>
            </tr>
            <tr>
                <td>OS</td>
                <td>{{ $os['bc'] ?? $os['base_curve'] ?? '—' }}</td>
                <td>{{ $os['power'] ?? '—' }}</td>
                <td>{{ $os['dia'] ?? $os['diameter'] ?? '—' }}</td>
                <td>{{ $os['cyl'] ?? '—' }}</td>
                <td>{{ $os['axis'] ?? '—' }}</td>
                <td>{{ $os['brand'] ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top:12px;"><strong>Modality:</strong> {{ $contactLens['modality'] ?? '—' }}
        &nbsp;|&nbsp; <strong>Wear:</strong> {{ $contactLens['wearSchedule'] ?? ($contactLens['wear'] ?? '—') }}</p>
    @if(!empty($contactLens['notes']))
        <p><strong>Notes:</strong> {{ $contactLens['notes'] }}</p>
    @endif

    <p class="muted" style="margin-top:24px;">Verify fit and follow-up per clinic protocol. Generated from EMR.</p>
</body>
</html>
