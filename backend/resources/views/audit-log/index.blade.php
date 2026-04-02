@extends('layouts.app')

@section('title', 'Audit Log')
@section('breadcrumb', 'Audit Log')

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-5">

    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Audit Log</h1>
            <p class="text-sm text-gray-500 mt-0.5">Track all clinical actions and changes across your clinic.</p>
        </div>
        <span class="px-3 py-1.5 bg-gray-100 text-gray-600 text-sm font-semibold rounded-xl">
            {{ number_format($logs->total()) }} entries
        </span>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('audit-log.index') }}"
          class="bg-white rounded-2xl border border-gray-100 p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Action</label>
                <select name="action"
                        class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Actions</option>
                    @foreach($actions as $act)
                    <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $act)) }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Record Type</label>
                <select name="model_type"
                        class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Types</option>
                    <option value="IpdAdmission"        {{ request('model_type') === 'IpdAdmission'        ? 'selected' : '' }}>IPD Admission</option>
                    <option value="Bed"                 {{ request('model_type') === 'Bed'                 ? 'selected' : '' }}>Bed</option>
                    <option value="PharmacyDispensing"  {{ request('model_type') === 'PharmacyDispensing'  ? 'selected' : '' }}>Pharmacy Dispensing</option>
                    <option value="PharmacyStock"       {{ request('model_type') === 'PharmacyStock'       ? 'selected' : '' }}>Pharmacy Stock</option>
                    <option value="lab_orders"          {{ request('model_type') === 'lab_orders'          ? 'selected' : '' }}>Lab Orders</option>
                    <option value="Invoice"             {{ request('model_type') === 'Invoice'             ? 'selected' : '' }}>Invoice</option>
                    <option value="Patient"             {{ request('model_type') === 'Patient'             ? 'selected' : '' }}>Patient</option>
                    <option value="Appointment"         {{ request('model_type') === 'Appointment'         ? 'selected' : '' }}>Appointment</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
                    Filter
                </button>
                <a href="{{ route('audit-log.index') }}"
                   class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-colors">
                    Reset
                </a>
            </div>
        </div>
    </form>

    {{-- Log Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Timestamp</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Record</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IP</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Changes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($logs as $log)
                    @php
                        $badgeClass = match($log->action ?? '') {
                            'created'           => 'bg-green-100 text-green-700',
                            'updated'           => 'bg-blue-100 text-blue-700',
                            'deleted'           => 'bg-red-100 text-red-700',
                            'discharged'        => 'bg-orange-100 text-orange-700',
                            'dispensed'         => 'bg-purple-100 text-purple-700',
                            'lab_results_saved' => 'bg-teal-100 text-teal-700',
                            'checked_in'        => 'bg-cyan-100 text-cyan-700',
                            'payment_recorded'  => 'bg-emerald-100 text-emerald-700',
                            default             => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50/60 transition-colors">
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <p class="text-gray-900 font-medium">{{ $log->created_at->format('d M Y') }}</p>
                            <p class="text-xs text-gray-400">{{ $log->created_at->format('H:i:s') }}</p>
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <p class="font-medium text-gray-900">{{ $log->user_name ?? 'System' }}</p>
                            @if($log->user_role)
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 uppercase">
                                {{ $log->user_role }}
                            </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                {{ ucfirst(str_replace('_', ' ', $log->action ?? '—')) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-600 max-w-xs">
                            <span title="{{ $log->description }}">{{ Str::limit($log->description, 70) }}</span>
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-gray-500">
                            <p>{{ class_basename($log->model_type ?? '') ?: '—' }}</p>
                            @if($log->model_id)
                            <p class="text-xs text-gray-400">#{{ $log->model_id }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-xs text-gray-400 font-mono">
                            {{ $log->ip_address ?? '—' }}
                        </td>
                        <td class="px-5 py-3.5">
                            @if($log->old_values || $log->new_values)
                            <button type="button"
                                    onclick="toggleChanges({{ $log->id }})"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                View diff
                            </button>
                            <div id="changes-{{ $log->id }}" class="hidden mt-2 space-y-1.5 text-xs">
                                @if($log->old_values)
                                <div>
                                    <p class="font-semibold text-red-600 mb-0.5">Before</p>
                                    <pre class="bg-red-50 border border-red-100 px-2 py-1.5 rounded-lg text-red-800 overflow-x-auto max-w-xs whitespace-pre-wrap break-all">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                                @endif
                                @if($log->new_values)
                                <div>
                                    <p class="font-semibold text-green-600 mb-0.5">After</p>
                                    <pre class="bg-green-50 border border-green-100 px-2 py-1.5 rounded-lg text-green-800 overflow-x-auto max-w-xs whitespace-pre-wrap break-all">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                                @endif
                            </div>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-sm text-gray-500">No audit log entries found.</p>
                            <p class="text-xs text-gray-400 mt-1">Audit events will appear here as clinical actions are performed.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
            {{ $logs->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>

<script>
function toggleChanges(id) {
    const el = document.getElementById('changes-' + id);
    if (el) el.classList.toggle('hidden');
}
</script>
@endsection
