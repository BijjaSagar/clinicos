<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescription — {{ $admission->patient->full_name ?? $admission->patient->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 20px; }
        }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <!-- Print Controls -->
    <div class="no-print max-w-3xl mx-auto mb-4 flex justify-between items-center">
        <a href="{{ route('ipd.show', $admission) }}" class="text-sm text-blue-600 hover:underline">&larr; Back to Admission</a>
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-semibold">Print Prescription</button>
    </div>

    <!-- Prescription -->
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow">
        <!-- Header -->
        <div class="border-b-2 border-blue-600 pb-4 mb-4">
            <h1 class="text-xl font-bold text-blue-800">{{ auth()->user()->clinic->name ?? 'ClinicOS' }}</h1>
            <p class="text-sm text-gray-500">{{ auth()->user()->clinic->address_line1 ?? '' }}{{ auth()->user()->clinic->city ? ', ' . auth()->user()->clinic->city : '' }}</p>
        </div>

        <!-- Patient & Doctor Info -->
        <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
            <div>
                <p><span class="font-semibold">Patient:</span> {{ $admission->patient->full_name ?? $admission->patient->name }}</p>
                <p><span class="font-semibold">Age/Gender:</span> {{ $admission->patient->date_of_birth ? \Carbon\Carbon::parse($admission->patient->date_of_birth)->age . ' yrs' : ($admission->patient->age_years ? $admission->patient->age_years . ' yrs' : 'N/A') }} / {{ ucfirst($admission->patient->gender ?? $admission->patient->sex ?? 'N/A') }}</p>
                <p><span class="font-semibold">IPD No:</span> {{ $admission->admission_number ?? 'IPD-'.str_pad($admission->id, 5, '0', STR_PAD_LEFT) }}</p>
            </div>
            <div class="text-right">
                <p><span class="font-semibold">Doctor:</span> Dr. {{ $admission->primaryDoctor->name ?? 'N/A' }}</p>
                <p><span class="font-semibold">Ward/Bed:</span> {{ $admission->ward->name ?? 'N/A' }} / {{ $admission->bed->bed_number ?? 'N/A' }}</p>
                <p><span class="font-semibold">Date:</span> {{ now()->format('d M Y') }}</p>
            </div>
        </div>

        <!-- Rx Symbol -->
        <div class="text-2xl font-bold text-blue-800 mb-3">&#8478;</div>

        <!-- Medications Table -->
        <table class="w-full text-sm mb-8">
            <thead>
                <tr class="border-b-2 border-gray-300">
                    <th class="text-left py-2 font-semibold">#</th>
                    <th class="text-left py-2 font-semibold">Medication</th>
                    <th class="text-left py-2 font-semibold">Dose</th>
                    <th class="text-left py-2 font-semibold">Route</th>
                    <th class="text-left py-2 font-semibold">Frequency</th>
                    <th class="text-left py-2 font-semibold">Duration</th>
                    <th class="text-left py-2 font-semibold">Instructions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($medicationOrders as $i => $med)
                <tr class="border-b border-gray-100">
                    <td class="py-2">{{ $i + 1 }}</td>
                    <td class="py-2 font-medium">{{ $med->drug_name }}</td>
                    <td class="py-2">{{ $med->dose ?? '-' }}</td>
                    <td class="py-2">{{ $med->route ?? 'Oral' }}</td>
                    <td class="py-2">{{ $med->frequency ?? '-' }}</td>
                    <td class="py-2">{{ $med->duration ?? '-' }}</td>
                    <td class="py-2 text-gray-600">{{ $med->instructions ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-4 text-center text-gray-400">No medications ordered</td></tr>
                @endforelse
            </tbody>
        </table>

        <!-- Diagnosis -->
        @if($admission->diagnosis_at_admission)
        <div class="mb-6 text-sm">
            <span class="font-semibold">Diagnosis:</span> {{ $admission->diagnosis_at_admission }}
        </div>
        @endif

        <!-- Signature -->
        <div class="mt-12 text-right">
            <div class="border-t border-gray-400 inline-block pt-2 px-8">
                <p class="font-semibold">Dr. {{ $admission->primaryDoctor->name ?? '' }}</p>
                <p class="text-xs text-gray-500">{{ $admission->primaryDoctor->qualification ?? '' }}</p>
            </div>
        </div>
    </div>
</body>
</html>
