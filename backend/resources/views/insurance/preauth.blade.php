@extends('layouts.app')

@section('title', 'Pre-authorization')
@section('breadcrumb', 'Insurance / Pre-auth')

@section('content')
<div class="p-6 max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('insurance.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Insurance dashboard</a>
        <h1 class="text-xl font-bold text-gray-900 mt-2">Pre-authorization</h1>
        <p class="text-sm text-gray-500">Patient: <strong>{{ $patient->name }}</strong> ({{ $patient->phone ?? '—' }})</p>
    </div>

    <form id="preauth-form" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">

        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">TPA</label>
            <select name="tpa_code" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @if(isset($savedTpaConfigs) && $savedTpaConfigs->count())
                <optgroup label="Configured for your clinic">
                    @foreach($savedTpaConfigs as $cfg)
                    <option value="{{ $cfg->tpa_code }}">{{ $cfg->tpa_name }} ({{ $cfg->tpa_code }})</option>
                    @endforeach
                </optgroup>
                @endif
                <optgroup label="Common TPAs">
                    @foreach($tpas as $code => $info)
                    <option value="{{ $info['code'] ?? $code }}">{{ $info['name'] ?? $code }}</option>
                    @endforeach
                </optgroup>
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Insurance company</label>
            <input type="text" name="insurance_company" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="As on card">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Policy number</label>
                <input type="text" name="policy_number" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Member ID</label>
                <input type="text" name="member_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Claim type</label>
                <select name="claim_type" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="cashless">Cashless</option>
                    <option value="reimbursement">Reimbursement</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Admission type</label>
                <select name="admission_type" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="planned">Planned</option>
                    <option value="emergency">Emergency</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Estimated amount (₹)</label>
            <input type="number" name="estimated_amount" step="0.01" min="0" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Admission date</label>
                <input type="date" name="admission_date" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Expected discharge</label>
                <input type="date" name="expected_discharge" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Treatment details</label>
            <textarea name="treatment_details" rows="4" required maxlength="2000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Procedure / planned care"></textarea>
        </div>

        <div id="preauth-msg" class="hidden text-sm rounded-lg px-4 py-3"></div>

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
            Submit pre-authorization
        </button>
    </form>
</div>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('preauth-form');
    const msg = document.getElementById('preauth-msg');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        msg.classList.add('hidden');
        const fd = new FormData(form);
        const payload = {
            patient_id: fd.get('patient_id'),
            tpa_code: fd.get('tpa_code'),
            insurance_company: fd.get('insurance_company'),
            policy_number: fd.get('policy_number'),
            member_id: fd.get('member_id'),
            claim_type: fd.get('claim_type'),
            admission_type: fd.get('admission_type'),
            estimated_amount: parseFloat(fd.get('estimated_amount')),
            admission_date: fd.get('admission_date'),
            expected_discharge: fd.get('expected_discharge') || null,
            treatment_details: fd.get('treatment_details'),
        };
        console.log('[preauth] submitting', { patient_id: payload.patient_id });
        try {
            const res = await fetch(@json(route('insurance.preauth.submit')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            console.log('[preauth] response', data);
            msg.classList.remove('hidden', 'bg-green-50', 'text-green-800', 'bg-red-50', 'text-red-800');
            if (res.ok && data.success) {
                msg.classList.add('bg-green-50', 'text-green-800');
                msg.textContent = data.message || 'Submitted successfully.';
            } else {
                msg.classList.add('bg-red-50', 'text-red-800');
                msg.textContent = data.error || data.message || 'Submission failed.';
            }
        } catch (err) {
            console.error('[preauth] error', err);
            msg.classList.remove('hidden');
            msg.classList.add('bg-red-50', 'text-red-800');
            msg.textContent = 'Network error. Try again.';
        }
    });
})();
</script>
@endpush
@endsection
