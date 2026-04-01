<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Spectacle prescription</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 8px; }
        .muted { color: #555; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: center; }
        th { background: #f3f4f6; }
        .hdr { margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="hdr">
        <h1>Spectacle prescription</h1>
        <div><strong>{{ $clinic->name ?? 'Clinic' }}</strong></div>
        <div class="muted">Generated {{ now()->format('d M Y, h:i A') }}</div>
    </div>
    <p><strong>Patient:</strong> {{ $patient->name }} @if($patient->phone) &nbsp;|&nbsp; {{ $patient->phone }} @endif</p>
    <p><strong>Prescriber:</strong> {{ $doctor->name ?? '—' }}</p>
    <p class="muted">Visit #{{ $visit->visit_number }} &nbsp;|&nbsp; Source: {{ $spectacle['source'] ?? '—' }}</p>

    <table>
        <thead>
            <tr>
                <th>Eye</th>
                <th>Sphere</th>
                <th>Cylinder</th>
                <th>Axis</th>
                <th>Add</th>
                <th>VA</th>
            </tr>
        </thead>
        <tbody>
            @php
                $od = $spectacle['od'] ?? [];
                $os = $spectacle['os'] ?? [];
            @endphp
            <tr>
                <td>OD</td>
                <td>{{ $od['sphere'] ?? '—' }}</td>
                <td>{{ $od['cylinder'] ?? '—' }}</td>
                <td>{{ $od['axis'] ?? '—' }}</td>
                <td>{{ $od['add'] ?? '—' }}</td>
                <td>{{ $od['va'] ?? '—' }}</td>
            </tr>
            <tr>
                <td>OS</td>
                <td>{{ $os['sphere'] ?? '—' }}</td>
                <td>{{ $os['cylinder'] ?? '—' }}</td>
                <td>{{ $os['axis'] ?? '—' }}</td>
                <td>{{ $os['add'] ?? '—' }}</td>
                <td>{{ $os['va'] ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top:12px;"><strong>PD (mm):</strong> Distance {{ $spectacle['pdDistance'] ?? ($spectacle['pd_distance'] ?? '—') }}
        &nbsp;|&nbsp; Near {{ $spectacle['pdNear'] ?? ($spectacle['pd_near'] ?? '—') }}</p>
    @if(!empty($spectacle['instructions']))
        <p><strong>Instructions:</strong> {{ $spectacle['instructions'] }}</p>
    @endif
    @if(!empty($spectacle['lensType']))
        <p><strong>Lens type:</strong> {{ $spectacle['lensType'] }}</p>
    @endif

    <p class="muted" style="margin-top:24px;">This document is generated from EMR data for clinical use. Verify before dispensing.</p>
</body>
</html>
