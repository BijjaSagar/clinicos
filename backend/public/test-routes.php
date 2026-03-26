<?php
/**
 * ClinicOS Route & Page Test Script
 * Tests all major routes and their functionality
 * DELETE THIS FILE AFTER TESTING!
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== ClinicOS Route & Page Testing ===\n\n";

// Bootstrap Laravel
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die("❌ ERROR: vendor/autoload.php not found!\n");
}
require $autoload;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Boot the application
$request = \Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "✅ Laravel bootstrapped\n\n";

// Check critical routes
$criticalRoutes = [
    'dashboard' => 'Dashboard',
    'schedule' => 'Schedule',
    'patients.index' => 'Patients List',
    'appointments.index' => 'Appointments',
    'emr.index' => 'EMR',
    'billing.index' => 'Billing',
    'payments.index' => 'Payments',
    'gst-reports.index' => 'GST Reports',
    'photo-vault.index' => 'Photo Vault',
    'prescriptions.index' => 'Prescriptions',
    'analytics.index' => 'Analytics',
    'settings.index' => 'Settings',
    'vendor.index' => 'Lab Orders',
    'abdm.index' => 'ABDM',
    'whatsapp.index' => 'WhatsApp',
];

echo "=== Route Check ===\n";
foreach ($criticalRoutes as $route => $name) {
    $exists = \Illuminate\Support\Facades\Route::has($route);
    $status = $exists ? '✅' : '❌';
    echo "{$status} {$name} ({$route})\n";
}

// Check critical controllers
$controllers = [
    'DashboardController',
    'PatientWebController',
    'AppointmentWebController',
    'EmrWebController',
    'BillingWebController',
    'PaymentWebController',
    'GstReportWebController',
    'PhotoVaultWebController',
    'PrescriptionWebController',
    'AnalyticsWebController',
    'SettingsWebController',
    'VendorWebController',
    'WhatsAppWebController',
];

echo "\n=== Controller Check ===\n";
foreach ($controllers as $controller) {
    $class = "App\\Http\\Controllers\\Web\\{$controller}";
    $exists = class_exists($class);
    $status = $exists ? '✅' : '❌';
    echo "{$status} {$controller}\n";
}

// Check critical views
$views = [
    'layouts.app',
    'dashboard.index',
    'patients.index',
    'patients.show',
    'appointments.index',
    'emr.index',
    'billing.index',
    'payments.index',
    'gst-reports.index',
    'photo-vault.index',
    'prescriptions.index',
    'analytics.index',
    'settings.index',
    'abdm.index',
];

echo "\n=== View Check ===\n";
foreach ($views as $view) {
    $exists = view()->exists($view);
    $status = $exists ? '✅' : '❌';
    echo "{$status} {$view}\n";
}

// Check database tables
echo "\n=== Database Tables Check ===\n";
try {
    $tables = ['patients', 'appointments', 'invoices', 'payments', 'prescriptions', 'patient_photos', 'visits', 'users', 'clinics'];
    foreach ($tables as $table) {
        $exists = \Illuminate\Support\Facades\Schema::hasTable($table);
        $status = $exists ? '✅' : '❌';
        $count = $exists ? \Illuminate\Support\Facades\DB::table($table)->count() : 0;
        echo "{$status} {$table} ({$count} rows)\n";
    }
} catch (\Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Check storage permissions
echo "\n=== Storage Check ===\n";
$storagePaths = [
    storage_path('app/public'),
    storage_path('app/public/patient_photos'),
    storage_path('framework/views'),
    storage_path('framework/cache'),
    storage_path('framework/sessions'),
    storage_path('logs'),
];

foreach ($storagePaths as $path) {
    $exists = file_exists($path);
    $writable = $exists && is_writable($path);
    $status = $writable ? '✅' : ($exists ? '⚠️ (not writable)' : '❌ (missing)');
    echo "{$status} " . basename($path) . "\n";
}

// Check if patient_photos directory exists, create if not
$photosDir = storage_path('app/public/patient_photos');
if (!file_exists($photosDir)) {
    if (mkdir($photosDir, 0755, true)) {
        echo "✅ Created patient_photos directory\n";
    } else {
        echo "❌ Failed to create patient_photos directory\n";
    }
}

echo "\n=== Authenticated User Check ===\n";
if (auth()->check()) {
    $user = auth()->user();
    echo "✅ Logged in as: {$user->name} ({$user->email})\n";
    echo "   Clinic ID: {$user->clinic_id}\n";
} else {
    echo "⚠️ Not logged in - test by visiting routes in browser\n";
}

echo "\n=== Test URLs ===\n";
$baseUrl = config('app.url');
echo "Dashboard:     {$baseUrl}/dashboard\n";
echo "Patients:      {$baseUrl}/patients\n";
echo "Schedule:      {$baseUrl}/schedule\n";
echo "Billing:       {$baseUrl}/billing\n";
echo "Payments:      {$baseUrl}/payments\n";
echo "GST Reports:   {$baseUrl}/gst-reports\n";
echo "Photo Vault:   {$baseUrl}/photo-vault\n";
echo "Prescriptions: {$baseUrl}/prescriptions\n";
echo "Analytics:     {$baseUrl}/analytics\n";
echo "Settings:      {$baseUrl}/settings\n";

echo "\n==========================================\n";
echo "⚠️ DELETE THIS FILE AFTER TESTING!\n";
echo "==========================================\n";
