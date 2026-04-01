@extends('layouts.app')

@section('title', 'ABDM HIU')
@section('breadcrumb', 'ABDM / HIU')

@section('content')
<div class="p-6 max-w-3xl mx-auto space-y-6">
    <h1 class="text-xl font-bold text-gray-900">Health Information User (HIU) — M3 scaffold</h1>
    <p class="text-sm text-gray-500">Record intended links to external HIPs / care contexts. Production requires ABDM gateway credentials and certificate setup.</p>

    @if(isset($hiuSchemaReady) && !$hiuSchemaReady)
    <div class="rounded-lg bg-amber-50 text-amber-900 px-4 py-3 text-sm border border-amber-100">
        HIU table not found. Run <code class="bg-amber-100 px-1 rounded">php artisan migrate</code> to save links here.
    </div>
    @endif

    @if(session('success'))
        <div class="rounded-lg bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold mb-4">Register interest / link</h2>
        <form method="POST" action="{{ route('abdm.hiu.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Patient</label>
                <select name="patient_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach($patients as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} @if($p->abha_id) ({{ $p->abha_id }}) @endif</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">HIP ID (remote)</label>
                <input type="text" name="hip_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="From gateway / facility registry">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Care context reference</label>
                <input type="text" name="care_context_reference" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Save</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 font-semibold">Saved rows</div>
        <ul class="divide-y divide-gray-100">
            @forelse($links as $row)
                <li class="px-4 py-3 text-sm">
                    <div class="font-medium">{{ $row->patient->name ?? 'Patient' }}</div>
                    <div class="text-xs text-gray-500">Status: {{ $row->status }} · HIP: {{ $row->hip_id ?? '—' }}</div>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-gray-500 text-sm">No HIU rows yet.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
