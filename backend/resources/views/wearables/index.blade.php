@extends('layouts.app')

@section('title', 'Wearable imports')
@section('breadcrumb', 'Wearables')

@section('content')
<div class="p-6 max-w-4xl mx-auto space-y-6">
    @if(isset($wearablesSchemaReady) && !$wearablesSchemaReady)
    <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
        Wearable readings table is missing. Run <code class="bg-amber-100 px-1 rounded">php artisan migrate</code> to import CSV data.
    </div>
    @endif
    <h1 class="text-xl font-bold text-gray-900">Wearable &amp; home device data</h1>
    <p class="text-sm text-gray-500">Import CSV exports from BP monitors, glucometers, or fitness apps (simple column mapping).</p>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold mb-2">Import CSV</h2>
        <p class="text-xs text-gray-500 mb-4">Header row should include columns like: recorded_at, systolic, diastolic, glucose, heart_rate (names are matched case-insensitively).</p>
        <form method="POST" action="{{ route('wearables.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Patient</label>
                <select name="patient_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach($patients as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Device type</label>
                <input type="text" name="device_type" value="bp_monitor" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">CSV file</label>
                <input type="file" name="file" required accept=".csv,.txt" class="text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Import</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 font-semibold">Recent readings</div>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-2">Patient</th>
                    <th class="px-4 py-2">When</th>
                    <th class="px-4 py-2">Device</th>
                    <th class="px-4 py-2">BP / BG / HR</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($readings as $r)
                    <tr>
                        <td class="px-4 py-2">{{ $r->patient->name ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $r->recorded_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $r->device_type }}</td>
                        <td class="px-4 py-2">
                            @if($r->systolic || $r->diastolic)
                                {{ $r->systolic }}/{{ $r->diastolic }}
                            @endif
                            @if($r->glucose_mg_dl) {{ $r->glucose_mg_dl }} mg/dL @endif
                            @if($r->heart_rate) HR {{ $r->heart_rate }} @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No readings imported.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($readings->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $readings->links() }}</div>
        @endif
    </div>
</div>
@endsection
