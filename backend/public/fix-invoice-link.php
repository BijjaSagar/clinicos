<?php
/**
 * Fix Invoice-Visit Linkage
 * 
 * This script helps link existing invoices to their visits.
 * DELETE THIS FILE AFTER USE!
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(\Illuminate\Http\Request::capture());

use App\Models\Invoice;
use App\Models\Visit;
use App\Models\Patient;

echo "<h2>Invoice-Visit Linkage Tool</h2>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;max-width:800px;margin:0 auto}table{width:100%;border-collapse:collapse}th,td{padding:8px;text-align:left;border:1px solid #ddd}th{background:#f5f5f5}.btn{display:inline-block;padding:8px 16px;background:#1447e6;color:white;text-decoration:none;border-radius:6px;margin:4px}</style>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invoice_id'], $_POST['visit_id'])) {
    $invoiceId = (int)$_POST['invoice_id'];
    $visitId = (int)$_POST['visit_id'];
    
    $invoice = Invoice::find($invoiceId);
    if ($invoice) {
        $invoice->visit_id = $visitId;
        $invoice->save();
        echo "<p style='color:green;font-weight:bold'>✅ Invoice #{$invoiceId} linked to Visit #{$visitId}</p>";
    }
}

// Get all invoices without visit_id
$unlinkedInvoices = Invoice::whereNull('visit_id')
    ->orWhere('visit_id', 0)
    ->with('patient')
    ->orderByDesc('created_at')
    ->get();

echo "<h3>Unlinked Invoices (" . $unlinkedInvoices->count() . ")</h3>";

if ($unlinkedInvoices->isEmpty()) {
    echo "<p style='color:green'>✅ All invoices are linked to visits!</p>";
} else {
    echo "<table>";
    echo "<tr><th>Invoice #</th><th>Patient</th><th>Total</th><th>Created</th><th>Action</th></tr>";
    
    foreach ($unlinkedInvoices as $invoice) {
        // Find possible visits for this patient
        $visits = Visit::where('patient_id', $invoice->patient_id)
            ->orderByDesc('created_at')
            ->get();
        
        echo "<tr>";
        echo "<td>#{$invoice->id} ({$invoice->invoice_number})</td>";
        echo "<td>" . ($invoice->patient->name ?? 'Unknown') . " (ID: {$invoice->patient_id})</td>";
        echo "<td>₹" . number_format($invoice->total, 2) . "</td>";
        echo "<td>" . $invoice->created_at->format('d M Y H:i') . "</td>";
        echo "<td>";
        
        if ($visits->isNotEmpty()) {
            echo "<form method='POST' style='display:inline'>";
            echo "<input type='hidden' name='invoice_id' value='{$invoice->id}'>";
            echo "<select name='visit_id' style='padding:4px;margin-right:8px'>";
            foreach ($visits as $visit) {
                $visitDate = $visit->created_at->format('d M Y H:i');
                $status = ucfirst($visit->status);
                echo "<option value='{$visit->id}'>Visit #{$visit->id} - {$visitDate} ({$status})</option>";
            }
            echo "</select>";
            echo "<button type='submit' style='padding:4px 12px;background:#059669;color:white;border:none;border-radius:4px;cursor:pointer'>Link</button>";
            echo "</form>";
        } else {
            echo "<em>No visits found</em>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Show all invoices summary
echo "<h3>All Invoices</h3>";
$allInvoices = Invoice::with(['patient', 'visit'])
    ->orderByDesc('created_at')
    ->limit(20)
    ->get();

echo "<table>";
echo "<tr><th>Invoice #</th><th>Patient</th><th>Visit ID</th><th>Total</th><th>Status</th></tr>";

foreach ($allInvoices as $invoice) {
    $visitLink = $invoice->visit_id 
        ? "<a href='/emr/{$invoice->patient_id}/{$invoice->visit_id}'>Visit #{$invoice->visit_id}</a>"
        : "<span style='color:red'>Not linked</span>";
    
    echo "<tr>";
    echo "<td>#{$invoice->id}</td>";
    echo "<td>" . ($invoice->patient->name ?? 'Unknown') . "</td>";
    echo "<td>{$visitLink}</td>";
    echo "<td>₹" . number_format($invoice->total, 2) . "</td>";
    echo "<td>" . ucfirst($invoice->payment_status ?? 'pending') . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr><p style='color:red;font-weight:bold'>⚠️ DELETE THIS FILE AFTER USE!</p>";
