@extends('layouts.app')

@section('title', 'GST Reports')
@section('breadcrumb', 'GST Reports')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">GST Reports</h1>
            <p class="text-sm text-gray-500 mt-0.5">Generate GSTR-1 and other GST compliance reports</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Report
            </button>
        </div>
    </div>

    {{-- Period Selector --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Month</label>
                <select name="month" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ ($month ?? now()->month) == $m ? 'selected' : '' }}>
                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                    </option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Year</label>
                <select name="year" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" {{ ($year ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Generate Report
                </button>
            </div>
        </form>
    </div>

    {{-- Clinic & GSTIN Info --}}
    @if(isset($clinic))
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 p-5">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="font-bold text-gray-900">{{ $clinic->name ?? 'Clinic' }}</h3>
                <p class="text-sm text-gray-600 mt-1">{{ $clinic->address_line1 ?? '' }}{{ $clinic->city ? ', ' . $clinic->city : '' }}</p>
                @if($clinic->gstin)
                <p class="text-sm font-mono text-blue-700 mt-2">GSTIN: {{ $clinic->gstin }}</p>
                @else
                <p class="text-sm text-amber-600 mt-2">GSTIN not configured - <a href="{{ route('settings.index') }}" class="underline">Update in Settings</a></p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Report Period</p>
                <p class="text-lg font-bold text-gray-900">{{ date('F Y', mktime(0, 0, 0, $month ?? now()->month, 1, $year ?? now()->year)) }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium">Total Invoices</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_invoices'] ?? 0 }}</p>
            <p class="text-xs text-green-600 mt-1">{{ $stats['paid_invoices'] ?? 0 }} paid</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium">Taxable Value</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">₹{{ number_format($stats['total_taxable'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Before GST</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium">Total GST</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">₹{{ number_format($stats['total_gst'] ?? 0) }}</p>
            <div class="flex gap-2 mt-1">
                <span class="text-xs text-gray-500">CGST: ₹{{ number_format($stats['total_cgst'] ?? 0) }}</span>
                <span class="text-xs text-gray-500">SGST: ₹{{ number_format($stats['total_sgst'] ?? 0) }}</span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium">Total Invoice Value</p>
            <p class="text-2xl font-bold text-green-600 mt-1">₹{{ number_format($stats['total_value'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Including GST</p>
        </div>
    </div>

    {{-- GST Rate Breakdown --}}
    @if(isset($gstByRate) && $gstByRate->count() > 0)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">GST Rate-wise Summary</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">GST Rate</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Invoices</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Taxable Value</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">CGST</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">SGST</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total Tax</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($gstByRate as $rate)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                {{ $rate->gst_rate ?? 0 }}%
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right text-sm text-gray-900">{{ $rate->invoice_count }}</td>
                        <td class="px-5 py-4 text-right text-sm font-medium text-gray-900">₹{{ number_format($rate->taxable_total ?? 0) }}</td>
                        <td class="px-5 py-4 text-right text-sm text-gray-600">₹{{ number_format($rate->cgst_total ?? 0) }}</td>
                        <td class="px-5 py-4 text-right text-sm text-gray-600">₹{{ number_format($rate->sgst_total ?? 0) }}</td>
                        <td class="px-5 py-4 text-right text-sm font-bold text-blue-600">₹{{ number_format(($rate->cgst_total ?? 0) + ($rate->sgst_total ?? 0)) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td class="px-5 py-3 text-sm font-bold text-gray-900">TOTAL</td>
                        <td class="px-5 py-3 text-right text-sm font-bold text-gray-900">{{ $gstByRate->sum('invoice_count') }}</td>
                        <td class="px-5 py-3 text-right text-sm font-bold text-gray-900">₹{{ number_format($gstByRate->sum('taxable_total')) }}</td>
                        <td class="px-5 py-3 text-right text-sm font-bold text-gray-900">₹{{ number_format($gstByRate->sum('cgst_total')) }}</td>
                        <td class="px-5 py-3 text-right text-sm font-bold text-gray-900">₹{{ number_format($gstByRate->sum('sgst_total')) }}</td>
                        <td class="px-5 py-3 text-right text-sm font-bold text-blue-600">₹{{ number_format($gstByRate->sum('cgst_total') + $gstByRate->sum('sgst_total')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    {{-- Monthly Trend --}}
    @if(isset($monthlyTrend) && count($monthlyTrend) > 0)
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-900 mb-4">6-Month GST Trend</h3>
        <div class="grid grid-cols-6 gap-4">
            @foreach($monthlyTrend as $trend)
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 font-medium">{{ $trend['month'] }}</p>
                <p class="text-lg font-bold text-gray-900 mt-1">₹{{ number_format($trend['gst']) }}</p>
                <p class="text-xs text-gray-400">{{ $trend['count'] }} inv</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- GSTR-1 B2C Summary --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">GSTR-1 B2C Summary</h3>
            <span class="text-xs text-gray-500">Business to Consumer (Unregistered)</span>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-4 gap-4 text-center">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">No. of Invoices</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ $b2cSummary['count'] ?? 0 }}</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">Taxable Value</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">₹{{ number_format($b2cSummary['taxable'] ?? 0) }}</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">CGST (9%)</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">₹{{ number_format($b2cSummary['cgst'] ?? 0) }}</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">SGST (9%)</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">₹{{ number_format($b2cSummary['sgst'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Invoice List --}}
    @if(isset($invoices) && $invoices->count() > 0)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Invoice Details</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Invoice No.</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Patient</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Taxable</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">CGST</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">SGST</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($invoices as $invoice)
                    @php
                        $taxable = ($invoice->subtotal ?? 0) - ($invoice->discount_amount ?? 0);
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('billing.show', $invoice) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                {{ $invoice->invoice_number ?? '#' . $invoice->id }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $invoice->invoice_date?->format('d M Y') ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-gray-900">{{ $invoice->patient?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-right text-gray-900">₹{{ number_format($taxable) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">₹{{ number_format($invoice->cgst_amount ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">₹{{ number_format($invoice->sgst_amount ?? 0) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">₹{{ number_format($invoice->total ?? 0) }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $statusColors = [
                                    'paid' => 'bg-green-100 text-green-700',
                                    'partial' => 'bg-amber-100 text-amber-700',
                                    'pending' => 'bg-red-100 text-red-700',
                                ];
                                $status = $invoice->payment_status ?? 'pending';
                            @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900">No invoices for this period</h3>
        <p class="text-gray-500 mt-1">Create invoices to generate GST reports.</p>
        <a href="{{ route('billing.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
            Create Invoice
        </a>
    </div>
    @endif
</div>
@endsection
