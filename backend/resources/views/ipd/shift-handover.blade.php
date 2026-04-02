@extends('layouts.app')
@section('title', 'Shift Handover')

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-4xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Shift Handover</h1>
        <p class="text-sm text-gray-500 mt-0.5">Document patient status for the incoming shift</p>
    </div>

    {{-- New Handover Form --}}
    <form method="POST" action="{{ route('shift-handover.store') }}"
          x-data="{ patientNotes: @json($admissions->map(fn($a) => ['admission_id' => $a->id, 'note' => ''])->values()->toArray()) }">
        @csrf
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-semibold text-gray-900">New Handover Note</h3>
            </div>
            <div class="p-6 space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Shift</label>
                        <select name="shift" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="morning">🌅 Morning (6am–2pm)</option>
                            <option value="afternoon">☀️ Afternoon (2pm–10pm)</option>
                            <option value="night">🌙 Night (10pm–6am)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Date</label>
                        <input type="date" name="handover_date" required
                               value="{{ today()->toDateString() }}"
                               class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Ward</label>
                        <select name="ward_id" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Wards</option>
                            @foreach($wards as $ward)
                            <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">General Handover Notes <span class="text-red-500">*</span></label>
                    <textarea name="general_notes" required rows="4"
                              placeholder="Overall ward status, pending tasks, important events during the shift, any instructions for the incoming team…"
                              class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                </div>

                @if($admissions->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Per-Patient Notes</label>
                    <div class="border border-gray-200 rounded-xl overflow-hidden divide-y divide-gray-100">
                        @foreach($admissions as $i => $admission)
                        <div class="flex items-start gap-3 p-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">
                                    {{ substr($admission->patient_name, 0, 1) }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900">{{ $admission->patient_name }}</p>
                                <p class="text-xs text-gray-400">{{ $admission->ward_name ?? '—' }} · Bed {{ $admission->bed_number ?? '—' }} · #{{ $admission->admission_number }}</p>
                                <input type="hidden" name="patient_notes[{{ $i }}][admission_id]" value="{{ $admission->id }}">
                                <textarea name="patient_notes[{{ $i }}][note]"
                                          placeholder="Patient status, pending investigations, special instructions…"
                                          rows="2"
                                          class="mt-1.5 w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
            <div class="px-6 pb-6">
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
                    Save Handover Note
                </button>
            </div>
        </div>
    </form>

    {{-- Recent Handovers --}}
    @if($recentNotes->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Recent Handovers</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($recentNotes as $note)
            <div class="px-6 py-4" x-data="{ expanded: false }">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full
                                {{ $note->shift === 'morning' ? 'bg-amber-100 text-amber-700' :
                                   ($note->shift === 'night'  ? 'bg-indigo-100 text-indigo-700' :
                                                                'bg-orange-100 text-orange-700') }}">
                                {{ ucfirst($note->shift) }}
                            </span>
                            <span class="text-sm font-medium text-gray-900">{{ $note->staff_name }}</span>
                            @if($note->ward_name)
                            <span class="text-xs text-gray-400">{{ $note->ward_name }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ \Carbon\Carbon::parse($note->handover_date)->format('d M Y') }} · {{ \Carbon\Carbon::parse($note->created_at)->diffForHumans() }}
                        </p>
                    </div>
                    <button @click="expanded = !expanded" class="text-xs text-blue-600 hover:text-blue-800 whitespace-nowrap">
                        <span x-text="expanded ? 'Collapse' : 'Read'"></span>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mt-2 line-clamp-2" :class="expanded ? 'line-clamp-none' : ''">{{ $note->general_notes }}</p>
                @if(!empty($note->patient_notes))
                <div x-show="expanded" x-cloak class="mt-3 space-y-2">
                    @foreach($note->patient_notes as $pn)
                    @if(!empty($pn['note']))
                    <div class="bg-gray-50 rounded-lg px-3 py-2 text-xs text-gray-600">
                        <span class="font-semibold">Admission #{{ $pn['admission_id'] }}:</span> {{ $pn['note'] }}
                    </div>
                    @endif
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
