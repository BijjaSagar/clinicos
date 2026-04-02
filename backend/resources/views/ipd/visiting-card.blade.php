<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Card – {{ $admission->patient->full_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; }

        @media print {
            body { margin: 0; padding: 0; background: white; }
            .no-print { display: none !important; }
            .card-wrapper { page-break-inside: avoid; }
            @page { size: 85mm 54mm; margin: 0; }
        }

        .card {
            width: 85mm;
            min-height: 54mm;
            border: 1.5px solid #1e40af;
            border-radius: 6px;
            padding: 8px 10px;
            background: white;
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, #1e40af, #3b82f6);
        }
        .hosp-name { font-size: 11px; font-weight: 700; color: #1e40af; margin-top: 4px; }
        .patient-name { font-size: 13px; font-weight: 700; color: #111827; margin: 4px 0 2px; }
        .info-row { display: flex; gap: 8px; font-size: 9px; color: #374151; margin-bottom: 2px; }
        .info-label { font-weight: 600; color: #6b7280; min-width: 50px; }
        .badge { display: inline-block; background: #dbeafe; color: #1e40af; border-radius: 4px; padding: 1px 5px; font-size: 9px; font-weight: 600; }
        .divider { border: none; border-top: 1px dashed #e5e7eb; margin: 5px 0; }
        .footer-note { font-size: 8px; color: #9ca3af; text-align: center; margin-top: 4px; }
    </style>
</head>
<body class="bg-gray-100 p-6" x-data="cardPrinter()" x-init="init()">

    {{-- ── Controls (hidden on print) ── --}}
    <div class="no-print max-w-2xl mx-auto mb-6 bg-white rounded-xl shadow p-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-gray-800">Print Admission Card</h1>
                <p class="text-sm text-gray-500">{{ $admission->patient->full_name }} &mdash; {{ $admission->admission_number ?? 'IPD-'.$admission->id }}</p>
            </div>
            <a href="{{ route('ipd.show', $admission) }}" class="text-sm text-blue-600 hover:underline">&larr; Back to Admission</a>
        </div>

        <div class="mt-4 flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Number of copies:</label>
            <div class="flex gap-2">
                @foreach([1,2,3,4,5] as $n)
                <button
                    onclick="setCopies({{ $n }})"
                    :id="'btn-{{ $n }}'"
                    class="w-9 h-9 rounded-lg border-2 font-bold text-sm transition-all"
                    :class="copies === {{ $n }} ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:border-blue-400'"
                    x-data
                    @click="$dispatch('set-copies', { n: {{ $n }} })"
                >{{ $n }}</button>
                @endforeach
            </div>
            <button
                onclick="window.print()"
                class="ml-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold text-sm flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Cards
            </button>
        </div>
    </div>

    {{-- ── Card preview grid ── --}}
    <div id="cards-container" class="max-w-2xl mx-auto flex flex-wrap gap-4 justify-center">
        @php
            $patientAge = $admission->patient->date_of_birth
                ? \Carbon\Carbon::parse($admission->patient->date_of_birth)->age . ' yrs'
                : ($admission->patient->age ? $admission->patient->age . ' yrs' : 'N/A');
            $clinicName = auth()->user()->clinic->name ?? config('app.name');
            $wardBed = ($admission->ward->name ?? 'N/A') . ' / Bed ' . ($admission->bed->bed_number ?? 'N/A');
            $admissionNo = $admission->admission_number ?? 'IPD-' . str_pad($admission->id, 5, '0', STR_PAD_LEFT);
            $admittedDate = \Carbon\Carbon::parse($admission->admitted_at ?? $admission->created_at)->format('d M Y, h:i A');
            $doctor = $admission->primaryDoctor->name ?? 'N/A';
            $emergency = $admission->emergency_contact_name ?? $admission->patient->emergency_contact_name ?? '';
            $emergencyPhone = $admission->emergency_contact_phone ?? $admission->patient->emergency_contact_phone ?? '';
        @endphp

        {{-- Default: show 1 card; JS will clone more --}}
        <div class="card-wrapper" id="card-template">
            <div class="card">
                <div class="hosp-name">{{ strtoupper($clinicName) }}</div>
                <div class="patient-name">{{ $admission->patient->full_name }}</div>
                <div class="info-row">
                    <span class="info-label">Age/Sex</span>
                    <span>{{ $patientAge }} / {{ ucfirst($admission->patient->gender ?? 'N/A') }}</span>
                    <span class="ml-auto"><span class="badge">{{ $admissionNo }}</span></span>
                </div>
                <hr class="divider">
                <div class="info-row"><span class="info-label">Ward/Bed</span><span>{{ $wardBed }}</span></div>
                <div class="info-row"><span class="info-label">Doctor</span><span>Dr. {{ $doctor }}</span></div>
                <div class="info-row"><span class="info-label">Admitted</span><span>{{ $admittedDate }}</span></div>
                <div class="info-row"><span class="info-label">Diagnosis</span><span class="truncate max-w-xs">{{ $admission->admission_diagnosis ?? 'Under evaluation' }}</span></div>
                @if($emergency)
                <hr class="divider">
                <div class="info-row"><span class="info-label">Emergency</span><span>{{ $emergency }}{{ $emergencyPhone ? ' · '.$emergencyPhone : '' }}</span></div>
                @endif
                <div class="footer-note">Please carry this card at all times during your stay</div>
            </div>
        </div>
    </div>

    <script>
        let copies = 1;

        function setCopies(n) {
            copies = n;
            // Update button styles
            [1,2,3,4,5].forEach(i => {
                const btn = document.getElementById('btn-' + i);
                if (btn) {
                    if (i === n) {
                        btn.className = btn.className.replace('border-gray-300 text-gray-700 hover:border-blue-400', 'border-blue-600 bg-blue-600 text-white');
                    } else {
                        btn.className = btn.className.replace('border-blue-600 bg-blue-600 text-white', 'border-gray-300 text-gray-700 hover:border-blue-400');
                    }
                }
            });

            const container = document.getElementById('cards-container');
            const template = document.getElementById('card-template');
            // Remove existing clones
            container.querySelectorAll('.card-clone').forEach(el => el.remove());
            // Add copies
            for (let i = 1; i < n; i++) {
                const clone = template.cloneNode(true);
                clone.id = 'card-clone-' + i;
                clone.classList.remove('card-wrapper');
                clone.classList.add('card-wrapper', 'card-clone');
                container.appendChild(clone);
            }
        }

        // Highlight copy 1 button on load
        window.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('btn-1');
            if (btn) btn.className = btn.className.replace('border-gray-300 text-gray-700 hover:border-blue-400', 'border-blue-600 bg-blue-600 text-white');
        });
    </script>
</body>
</html>
