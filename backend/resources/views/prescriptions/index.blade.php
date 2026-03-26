@extends('layouts.app')

@section('title', 'Prescriptions')
@section('breadcrumb', 'Prescriptions')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Prescriptions</h1>
            <p class="text-sm text-gray-500 mt-0.5">Digital prescriptions with drug database</p>
        </div>
        <a href="{{ route('emr.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Prescription
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium">Today</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['today'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Prescriptions written</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium">This Week</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['week'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Prescriptions</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium">Sent via WhatsApp</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['sent_via_whatsapp'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">This month</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium">Templates</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">{{ $stats['templates'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Saved</p>
        </div>
    </div>

    {{-- Top Drugs This Month --}}
    @if(isset($topDrugs) && $topDrugs->count() > 0)
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Top Prescribed Drugs (This Month)</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($topDrugs as $drug)
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-teal-50 border border-teal-200 rounded-full">
                <span class="text-sm font-medium text-teal-700">{{ $drug->drug_name }}</span>
                <span class="text-xs font-bold text-teal-500 bg-teal-100 px-1.5 py-0.5 rounded-full">{{ $drug->count }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Patient</label>
                <select name="patient_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[180px]">
                    <option value="">All Patients</option>
                    @foreach($patients ?? [] as $patient)
                    <option value="{{ $patient->id }}" {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                        {{ $patient->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Doctor</label>
                <select name="doctor_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Doctors</option>
                    @foreach($doctors ?? [] as $doctor)
                    <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>
                        {{ $doctor->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">From Date</label>
                <input type="date" name="from" value="{{ request('from') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">To Date</label>
                <input type="date" name="to" value="{{ request('to') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">WhatsApp</label>
                <select name="sent" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All</option>
                    <option value="yes" {{ request('sent') === 'yes' ? 'selected' : '' }}>Sent</option>
                    <option value="no" {{ request('sent') === 'no' ? 'selected' : '' }}>Not Sent</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Filter
                </button>
                <a href="{{ route('prescriptions.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Clear
                </a>
            </div>
        </form>
    </div>

    {{-- Prescriptions List --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Prescriptions</h3>
        </div>
        
        @if(isset($prescriptions) && (is_object($prescriptions) ? $prescriptions->count() : count($prescriptions)) > 0)
        <div class="divide-y divide-gray-100">
            @foreach($prescriptions as $prescription)
            <div class="p-5 hover:bg-gray-50 transition-colors">
                <div class="flex items-start gap-4">
                    {{-- Patient Avatar --}}
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center text-white font-bold flex-shrink-0">
                        {{ strtoupper(substr($prescription->patient?->name ?? 'P', 0, 1)) }}
                    </div>
                    
                    {{-- Prescription Details --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900">{{ $prescription->patient?->name ?? 'Unknown Patient' }}</h4>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    by {{ $prescription->doctor?->name ?? 'Unknown Doctor' }}
                                    · {{ $prescription->created_at?->format('d M Y, h:i A') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($prescription->whatsapp_sent_at)
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                    </svg>
                                    Sent
                                </span>
                                @else
                                <span class="inline-flex px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-semibold">
                                    Not sent
                                </span>
                                @endif
                                
                                @if($prescription->isValid())
                                <span class="inline-flex px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                    Valid
                                </span>
                                @else
                                <span class="inline-flex px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-semibold">
                                    Expired
                                </span>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Drugs List --}}
                        @if($prescription->drugs && $prescription->drugs->count() > 0)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($prescription->drugs->take(5) as $drug)
                            <div class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 rounded-lg text-xs">
                                <span class="font-medium text-gray-700">{{ $drug->drug_name }}</span>
                                @if($drug->strength)
                                <span class="text-gray-500">{{ $drug->strength }}</span>
                                @endif
                            </div>
                            @endforeach
                            @if($prescription->drugs->count() > 5)
                            <span class="text-xs text-gray-400">+{{ $prescription->drugs->count() - 5 }} more</span>
                            @endif
                        </div>
                        @endif
                        
                        {{-- Actions --}}
                        <div class="mt-3 flex items-center gap-3">
                            @if($prescription->pdf_url)
                            <a href="{{ $prescription->pdf_url }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Download PDF
                            </a>
                            @endif
                            @if($prescription->visit_id)
                            <a href="{{ route('emr.show', ['patient' => $prescription->patient_id, 'visit' => $prescription->visit_id]) }}" class="text-xs text-gray-600 hover:text-gray-800 font-medium flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                View Visit
                            </a>
                            @endif
                            @if($prescription->patient_id)
                            <a href="{{ route('patients.show', $prescription->patient_id) }}" class="text-xs text-gray-600 hover:text-gray-800 font-medium flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Patient Profile
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        {{-- Pagination --}}
        @if(method_exists($prescriptions, 'links'))
        <div class="px-5 py-4 border-t border-gray-200">
            {{ $prescriptions->withQueryString()->links() }}
        </div>
        @endif
        @else
        <div class="p-12 text-center">
            <div class="w-16 h-16 mx-auto bg-teal-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">No prescriptions yet</h3>
            <p class="text-gray-500 mt-1">Prescriptions are created from patient EMR visits.</p>
            <a href="{{ route('emr.index') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Go to EMR
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
