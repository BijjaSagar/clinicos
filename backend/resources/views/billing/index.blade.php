@extends('layouts.app')

@section('title', 'Billing')
@section('breadcrumb', 'Invoices & Payments')

@section('content')
{{-- Handle WhatsApp redirect --}}
@if(session('whatsapp_url'))
<script>
    window.open('{{ session('whatsapp_url') }}', '_blank');
</script>
@endif

<div class="p-6 space-y-6">
    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 font-medium">Today's Revenue</p>
            <p class="text-2xl font-extrabold text-gray-900 font-display mt-1">₹{{ number_format($stats['total_today'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 font-medium">Pending Collections</p>
            <p class="text-2xl font-extrabold text-amber-600 font-display mt-1">₹{{ number_format($stats['pending'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500 font-medium">This Month</p>
            <p class="text-2xl font-extrabold text-green-600 font-display mt-1">₹{{ number_format($stats['collected_month'] ?? 0) }}</p>
        </div>
    </div>

    {{-- Invoices Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-900">Recent Invoices</h3>
            <a href="{{ route('billing.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                New Invoice
            </a>
        </div>

        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice #</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Patient</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-4">
                        <span class="font-semibold text-gray-900 font-display">{{ $invoice->invoice_number }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ $invoice->patient->name }}</p>
                            <p class="text-sm text-gray-500">{{ $invoice->patient->phone }}</p>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500">
                        {{ $invoice->created_at->format('d M Y') }}
                    </td>
                    <td class="px-5 py-4">
                        <p class="font-semibold text-gray-900">₹{{ number_format($invoice->total ?? 0) }}</p>
                        @php $balanceDue = ($invoice->total ?? 0) - ($invoice->paid ?? 0); @endphp
                        @if($balanceDue > 0)
                        <p class="text-xs text-amber-600">Due: ₹{{ number_format($balanceDue) }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        @php
                            $status = $invoice->payment_status ?? 'pending';
                            $statusClasses = [
                                'paid' => 'bg-green-100 text-green-700',
                                'partial' => 'bg-amber-100 text-amber-700',
                                'pending' => 'bg-red-100 text-red-700',
                                'refunded' => 'bg-gray-100 text-gray-700',
                                'void' => 'bg-gray-100 text-gray-700',
                            ];
                        @endphp
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ ucfirst($status) }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('billing.show', $invoice) }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('billing.pdf', $invoice) }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </a>
                            <button onclick="sendWhatsApp({{ $invoice->id }})" class="p-2 text-green-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-gray-500">
                        No invoices found. Create your first invoice.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($invoices->hasPages())
        <div class="px-5 py-4 border-t border-gray-200">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
