@extends('layouts.app')

@section('title', 'Referrals')
@section('breadcrumb', 'Referrals')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6">
    <h1 class="text-xl font-bold text-gray-900">Referral letters</h1>
    <p class="text-sm text-gray-500">Track referrals to other specialists or hospitals.</p>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    @if(isset($patients) && $patients->count() > 0)
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">New referral</h2>
        <form method="POST" action="{{ route('referrals.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Patient</label>
                <select name="patient_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach($patients as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">To specialty</label>
                    <input type="text" name="to_specialty" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="e.g. Cardiology">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Urgency</label>
                    <select name="urgency" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="routine">Routine</option>
                        <option value="urgent">Urgent</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Facility / doctor</label>
                <input type="text" name="to_facility_name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm mb-2" placeholder="Hospital or clinic name">
                <input type="text" name="to_doctor_name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Consultant name (optional)">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Reason</label>
                <textarea name="reason" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Clinical summary</label>
                <textarea name="clinical_summary" rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Save draft</button>
        </form>
    </div>
    @else
        <div class="rounded-lg bg-amber-50 text-amber-900 px-4 py-3 text-sm border border-amber-100">Add patients before creating referrals.</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 font-semibold text-gray-900">Recent</div>
        <div class="divide-y divide-gray-100">
            @forelse($referrals as $ref)
                <div class="px-4 py-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="font-medium text-gray-900">{{ $ref->patient->name ?? 'Patient' }}</div>
                        <div class="text-xs text-gray-500">{{ $ref->to_specialty ?? '—' }} · {{ $ref->status }}</div>
                    </div>
                    <form method="POST" action="{{ route('referrals.status', $ref) }}" class="flex items-center gap-2">
                        @csrf
                        <select name="status" class="text-sm rounded border border-gray-300 px-2 py-1">
                            <option value="draft" @selected($ref->status==='draft')>Draft</option>
                            <option value="sent" @selected($ref->status==='sent')>Sent</option>
                            <option value="acknowledged" @selected($ref->status==='acknowledged')>Acknowledged</option>
                            <option value="completed" @selected($ref->status==='completed')>Completed</option>
                            <option value="cancelled" @selected($ref->status==='cancelled')>Cancelled</option>
                        </select>
                        <button type="submit" class="text-sm text-blue-600 font-medium">Update</button>
                    </form>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-gray-500 text-sm">No referrals yet.</div>
            @endforelse
        </div>
        @if($referrals->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $referrals->links() }}</div>
        @endif
    </div>
</div>
@endsection
