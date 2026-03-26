<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .container {
            padding: 30px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }
        .clinic-name {
            font-size: 24px;
            font-weight: bold;
            color: #1a56db;
            margin-bottom: 5px;
        }
        .clinic-info {
            color: #666;
            font-size: 11px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        .invoice-number {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .invoice-date {
            font-size: 11px;
            color: #888;
        }
        .divider {
            border-top: 2px solid #1a56db;
            margin: 20px 0;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .patient-name {
            font-size: 16px;
            font-weight: bold;
        }
        .patient-info {
            color: #666;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 12px 10px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            border-bottom: 2px solid #e9ecef;
        }
        th.right {
            text-align: right;
        }
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #e9ecef;
        }
        td.right {
            text-align: right;
        }
        .summary {
            margin-top: 20px;
            margin-left: auto;
            width: 250px;
        }
        .summary-row {
            display: table;
            width: 100%;
            padding: 8px 0;
        }
        .summary-label {
            display: table-cell;
            color: #666;
        }
        .summary-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
        }
        .summary-total {
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 5px;
        }
        .summary-total .summary-label,
        .summary-total .summary-value {
            font-size: 16px;
            color: #1a56db;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            color: #888;
            font-size: 10px;
        }
        .status-paid {
            display: inline-block;
            padding: 5px 15px;
            background: #d1fae5;
            color: #065f46;
            font-weight: bold;
            font-size: 11px;
            border-radius: 20px;
        }
        .status-pending {
            display: inline-block;
            padding: 5px 15px;
            background: #fee2e2;
            color: #991b1b;
            font-weight: bold;
            font-size: 11px;
            border-radius: 20px;
        }
        .gstin {
            background: #f0f9ff;
            padding: 10px;
            margin-top: 20px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="header-left">
                <div class="clinic-name">{{ $invoice->clinic->name ?? 'Clinic' }}</div>
                <div class="clinic-info">
                    {{ $invoice->clinic->address_line1 ?? '' }}<br>
                    {{ $invoice->clinic->city ?? '' }}{{ $invoice->clinic->state ? ', ' . $invoice->clinic->state : '' }} {{ $invoice->clinic->pincode ?? '' }}<br>
                    Phone: {{ $invoice->clinic->phone ?? 'N/A' }}<br>
                    Email: {{ $invoice->clinic->email ?? 'N/A' }}
                </div>
                @if($invoice->clinic->gstin ?? false)
                <div class="gstin">
                    <strong>GSTIN:</strong> {{ $invoice->clinic->gstin }}
                </div>
                @endif
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
                <div class="invoice-date">
                    Date: {{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : $invoice->created_at->format('d M Y') }}
                </div>
                <div style="margin-top: 15px;">
                    @if($invoice->payment_status === 'paid')
                    <span class="status-paid">PAID</span>
                    @else
                    <span class="status-pending">{{ strtoupper($invoice->payment_status) }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="divider"></div>

        {{-- Bill To --}}
        <div class="section">
            <div class="section-title">Bill To</div>
            <div class="patient-name">{{ $invoice->patient->name ?? 'Patient' }}</div>
            <div class="patient-info">
                Phone: {{ $invoice->patient->phone ?? 'N/A' }}<br>
                @if($invoice->patient->email ?? false)
                Email: {{ $invoice->patient->email }}<br>
                @endif
                Patient ID: {{ $invoice->patient->patient_uid ?? $invoice->patient->id }}
            </div>
        </div>

        {{-- Items Table --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 40%">Description</th>
                    <th style="width: 10%">SAC</th>
                    <th class="right" style="width: 10%">Qty</th>
                    <th class="right" style="width: 15%">Rate</th>
                    <th class="right" style="width: 10%">GST</th>
                    <th class="right" style="width: 15%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items ?? [] as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->sac_code ?? '-' }}</td>
                    <td class="right">{{ number_format($item->quantity, 0) }}</td>
                    <td class="right">₹{{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">{{ number_format($item->gst_rate ?? 18, 0) }}%</td>
                    <td class="right">₹{{ number_format($item->total, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #888;">No items</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Summary --}}
        <div class="summary">
            <div class="summary-row">
                <span class="summary-label">Subtotal</span>
                <span class="summary-value">₹{{ number_format($invoice->subtotal ?? 0, 2) }}</span>
            </div>
            @if(($invoice->discount_amount ?? 0) > 0)
            <div class="summary-row">
                <span class="summary-label">Discount</span>
                <span class="summary-value">-₹{{ number_format($invoice->discount_amount, 2) }}</span>
            </div>
            @endif
            <div class="summary-row">
                <span class="summary-label">CGST (9%)</span>
                <span class="summary-value">₹{{ number_format($invoice->cgst_amount ?? 0, 2) }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">SGST (9%)</span>
                <span class="summary-value">₹{{ number_format($invoice->sgst_amount ?? 0, 2) }}</span>
            </div>
            <div class="summary-row summary-total">
                <span class="summary-label">Total</span>
                <span class="summary-value">₹{{ number_format($invoice->total ?? 0, 2) }}</span>
            </div>
            @if(($invoice->paid ?? 0) > 0)
            <div class="summary-row">
                <span class="summary-label">Paid</span>
                <span class="summary-value" style="color: #065f46;">₹{{ number_format($invoice->paid, 2) }}</span>
            </div>
            @php $balance = ($invoice->total ?? 0) - ($invoice->paid ?? 0); @endphp
            @if($balance > 0)
            <div class="summary-row">
                <span class="summary-label"><strong>Balance Due</strong></span>
                <span class="summary-value" style="color: #991b1b;">₹{{ number_format($balance, 2) }}</span>
            </div>
            @endif
            @endif
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>Thank you for choosing {{ $invoice->clinic->name ?? 'our clinic' }}!</p>
            <p style="margin-top: 5px;">This is a computer-generated invoice and does not require a signature.</p>
            @if($invoice->clinic->settings['payment_terms'] ?? false)
            <p style="margin-top: 10px; font-style: italic;">{{ $invoice->clinic->settings['payment_terms'] }}</p>
            @endif
        </div>
    </div>
</body>
</html>
