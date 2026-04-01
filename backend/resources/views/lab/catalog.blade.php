@extends('layouts.app')

@section('title', 'Lab Test Catalog')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('laboratory.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Laboratory
            </a>
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Lab Test Catalog</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage available lab tests and their configurations</p>
        </div>
        <div x-data="{ open: false }">
            <button @click="open = true" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-blue text-white text-sm font-medium rounded-lg hover:bg-brand-blue-dark transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Test
            </button>

            {{-- Add Test Modal --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="open = false">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="open = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.stop>
                    <div class="flex items-center justify-between p-6 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900">Add New Lab Test</h2>
                        <button @click="open = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('laboratory.catalog.store') }}" class="p-6 space-y-5">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Test Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" required placeholder="e.g. Complete Blood Count"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Test Code</label>
                                <input type="text" name="code" placeholder="e.g. CBC"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <input type="text" name="category" placeholder="e.g. Haematology"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sample Type</label>
                                <input type="text" name="sample_type" placeholder="e.g. Blood, Urine"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                                <input type="text" name="unit" placeholder="e.g. mg/dL, g/L"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹)</label>
                                <input type="number" name="price" min="0" step="0.01" placeholder="0.00"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Turnaround Time (hours)</label>
                                <input type="number" name="turnaround_hours" min="1" placeholder="24"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reference Range</label>
                                <textarea name="reference_range" rows="2" placeholder="e.g. Male: 13.5–17.5 g/dL, Female: 12.0–15.5 g/dL"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors resize-none"></textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="2" placeholder="Any special instructions or notes..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors resize-none"></textarea>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                            <button type="button" @click="open = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-brand-blue rounded-lg hover:bg-brand-blue-dark transition-colors shadow-sm">
                                Add Test
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Total Tests</p>
            <p class="text-3xl font-bold text-gray-900">{{ $tests->total() ?? count($tests) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 mb-1">Active Tests</p>
            <p class="text-3xl font-bold text-gray-900">{{ $activeCount ?? $tests->where('is_active', true)->count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-brand-blue mb-1">Categories</p>
            <p class="text-3xl font-bold text-gray-900">{{ $categoryCount ?? $tests->pluck('category')->unique()->filter()->count() }}</p>
        </div>
    </div>

    {{-- Search & Filter --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
        <form method="GET" action="{{ route('laboratory.catalog') }}" class="flex flex-col sm:flex-row gap-3 p-4">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by test name or code..."
                    class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors">
            </div>
            <div class="sm:w-48">
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors bg-white">
                    <option value="">All Categories</option>
                    @foreach($categories ?? [] as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-brand-blue text-white text-sm font-medium rounded-lg hover:bg-brand-blue-dark transition-colors">
                Filter
            </button>
            @if(request('search') || request('category'))
                <a href="{{ route('laboratory.catalog') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors text-center">
                    Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Tests Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Test Name</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Code</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Category</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Sample Type</th>
                        <th class="text-right px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Price (₹)</th>
                        <th class="text-center px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">TAT (hrs)</th>
                        <th class="text-center px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="text-center px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tests ?? [] as $test)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4">
                            <p class="font-medium text-gray-900">{{ $test->name }}</p>
                            @if($test->reference_range)
                                <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">Ref: {{ $test->reference_range }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <span class="font-mono text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded">{{ $test->code ?? '—' }}</span>
                        </td>
                        <td class="px-4 py-4 text-gray-600">{{ $test->category ?? '—' }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $test->sample_type ?? '—' }}</td>
                        <td class="px-4 py-4 text-right font-medium text-gray-900">
                            {{ $test->price ? '₹' . number_format($test->price, 2) : '—' }}
                        </td>
                        <td class="px-4 py-4 text-center text-gray-600">{{ $test->turnaround_hours ?? '—' }}</td>
                        <td class="px-4 py-4 text-center">
                            <form method="POST" action="{{ route('laboratory.catalog.store') }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="id" value="{{ $test->id }}">
                                <input type="hidden" name="is_active" value="{{ $test->is_active ? '0' : '1' }}">
                                <button type="submit"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium transition-colors
                                    {{ $test->is_active
                                        ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                        : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $test->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                    {{ $test->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="#" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-brand-blue bg-brand-blue-light rounded-lg hover:bg-blue-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </a>
                                <form method="POST" action="#" class="inline" onsubmit="return confirm('Deactivate this test?')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="id" value="{{ $test->id }}">
                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        Deactivate
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                                <p class="text-gray-500 font-medium">No lab tests found</p>
                                <p class="text-sm text-gray-400">Add your first test using the "Add Test" button above.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($tests) && method_exists($tests, 'links'))
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $tests->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
