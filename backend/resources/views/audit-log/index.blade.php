@extends('layouts.app')

@section('title', 'Audit Log')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Audit Log</h1>
        <p class="mt-1 text-sm text-gray-500">Track all clinical actions and changes across your clinic.</p>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('audit-log.index') }}" class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Action --}}
            <div>
                <label for="action" class="block text-xs font-medium text-gray-700 mb-1">Action</label>
                <select name="action" id="action" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Actions</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $act)) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Model Type --}}
            <div>
                <label for="model_type" class="block text-xs font-medium text-gray-700 mb-1">Model Type</label>
                <select name="model_type" id="model_type" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Models</option>
                    <option value="IpdAdmission" {{ request('model_type') === 'IpdAdmission' ? 'selected' : '' }}>IPD Admission</option>
                    <option value="Bed" {{ request('model_type') === 'Bed' ? 'selected' : '' }}>Bed</option>
                    <option value="PharmacyDispensing" {{ request('model_type') === 'PharmacyDispensing' ? 'selected' : '' }}>Pharmacy Dispensing</option>
                    <option value="PharmacyStock" {{ request('model_type') === 'PharmacyStock' ? 'selected' : '' }}>Pharmacy Stock</option>
                    <option value="lab_orders" {{ request('model_type') === 'lab_orders' ? 'selected' : '' }}>Lab Orders</option>
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label for="date_from" class="block text-xs font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            {{-- Date To --}}
            <div>
                <label for="date_to" class="block text-xs font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            {{-- Buttons --}}
            <div class="flex items-end gap-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Filter
                </button>
                <a href="{{ route('audit-log.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200">
                    Reset
                </a>
            </div>
        </div>
    </form>

    {{-- Results Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Changes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50">
                            {{-- Timestamp --}}
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ $log->created_at->format('d M Y') }}<br>
                                <span class="text-xs text-gray-400">{{ $log->created_at->format('H:i:s') }}</span>
                            </td>

                            {{-- User --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $log->user_name ?? 'System' }}</div>
                                @if($log->user_role)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ ucfirst($log->user_role) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Action Badge --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $badgeColor = match($log->action) {
                                        'created' => 'bg-green-100 text-green-800',
                                        'updated' => 'bg-blue-100 text-blue-800',
                                        'deleted' => 'bg-red-100 text-red-800',
                                        'discharged' => 'bg-orange-100 text-orange-800',
                                        'dispensed' => 'bg-purple-100 text-purple-800',
                                        'lab_results_saved' => 'bg-teal-100 text-teal-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                                    {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                </span>
                            </td>

                            {{-- Description --}}
                            <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate" title="{{ $log->description }}">
                                {{ Str::limit($log->description, 60) }}
                            </td>

                            {{-- Model --}}
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ class_basename($log->model_type ?? '') }}
                                @if($log->model_id)
                                    <span class="text-xs text-gray-400">#{{ $log->model_id }}</span>
                                @endif
                            </td>

                            {{-- Changes (expandable) --}}
                            <td class="px-4 py-3 text-sm">
                                @if($log->old_values || $log->new_values)
                                    <button
                                        type="button"
                                        onclick="toggleChanges({{ $log->id }})"
                                        class="text-indigo-600 hover:text-indigo-800 text-xs font-medium underline"
                                    >
                                        View Changes
                                    </button>
                                    <div id="changes-{{ $log->id }}" class="hidden mt-2 text-xs">
                                        @if($log->old_values)
                                            <div class="mb-1">
                                                <span class="font-semibold text-red-600">Old:</span>
                                                <pre class="mt-0.5 bg-red-50 p-2 rounded text-red-800 overflow-x-auto max-w-sm">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        @endif
                                        @if($log->new_values)
                                            <div>
                                                <span class="font-semibold text-green-600">New:</span>
                                                <pre class="mt-0.5 bg-green-50 p-2 rounded text-green-800 overflow-x-auto max-w-sm">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="mt-2 text-sm">No audit log entries found.</p>
                                <p class="text-xs text-gray-400">Audit events will appear here as clinical actions are performed.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    function toggleChanges(id) {
        const el = document.getElementById('changes-' + id);
        if (el) {
            el.classList.toggle('hidden');
        }
    }
</script>
@endsection
