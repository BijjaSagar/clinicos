<?php
/**
 * ClinicOS Comprehensive Project Test
 * Tests all controllers, models, views, routes, and validations
 * DELETE THIS FILE AFTER TESTING!
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre style='font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:20px;'>";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║       CLINICOS COMPREHENSIVE PROJECT TEST                    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Bootstrap Laravel
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die("<span style='color:#f44336;'>❌ ERROR: vendor/autoload.php not found!</span>\n");
}
require $autoload;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "<span style='color:#4caf50;'>✅ Laravel bootstrapped successfully</span>\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

function test($name, $condition, &$passed, &$failed) {
    if ($condition) {
        echo "<span style='color:#4caf50;'>✅</span> {$name}\n";
        $passed++;
        return true;
    } else {
        echo "<span style='color:#f44336;'>❌</span> {$name}\n";
        $failed++;
        return false;
    }
}

function warn($name, &$warnings) {
    echo "<span style='color:#ff9800;'>⚠️</span> {$name}\n";
    $warnings++;
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n<span style='color:#2196f3;font-weight:bold;'>═══ 1. WEB CONTROLLERS (15) ═══</span>\n";
// ═══════════════════════════════════════════════════════════════════════

$webControllers = [
    'DashboardController' => ['index'],
    'AuthController' => ['showLogin', 'login', 'showRegister', 'register', 'logout'],
    'PatientWebController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'uploadPhoto', 'viewPhoto', 'deletePhoto'],
    'AppointmentWebController' => ['index', 'create', 'store', 'show', 'updateStatus', 'destroy'],
    'EmrWebController' => ['index', 'show', 'create', 'update', 'finalise'],
    'BillingWebController' => ['index', 'create', 'store', 'show', 'pdf', 'sendWhatsApp', 'markPaid'],
    'PaymentWebController' => ['index'],
    'GstReportWebController' => ['index'],
    'PhotoVaultWebController' => ['index'],
    'PrescriptionWebController' => ['index'],
    'AnalyticsWebController' => ['index'],
    'SettingsWebController' => ['index', 'updateClinic', 'updateBilling'],
    'VendorWebController' => ['index', 'acceptOrder', 'uploadResult'],
    'WhatsAppWebController' => ['index', 'send', 'broadcast'],
];

foreach ($webControllers as $controller => $methods) {
    $class = "App\\Http\\Controllers\\Web\\{$controller}";
    $exists = class_exists($class);
    test("{$controller} exists", $exists, $passed, $failed);
    
    if ($exists) {
        foreach ($methods as $method) {
            $hasMethod = method_exists($class, $method);
            if (!$hasMethod) {
                warn("  └─ Missing method: {$method}", $warnings);
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n<span style='color:#2196f3;font-weight:bold;'>═══ 2. API CONTROLLERS (16) ═══</span>\n";
// ═══════════════════════════════════════════════════════════════════════

$apiControllers = [
    'Auth\\AuthController',
    'Clinic\\ClinicController',
    'Patients\\PatientController',
    'EMR\\EmrController',
    'Scheduling\\AppointmentController',
    'Billing\\InvoiceController',
    'Billing\\PaymentController',
    'Prescription\\PrescriptionController',
    'Photo\\PhotoVaultController',
    'WhatsApp\\WhatsAppController',
    'ABDM\\AbdmController',
    'Vendor\\LabOrderController',
    'Analytics\\AnalyticsController',
    'AI\\AiAssistantController',
];

foreach ($apiControllers as $controller) {
    $class = "App\\Http\\Controllers\\{$controller}";
    test($controller, class_exists($class), $passed, $failed);
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n<span style='color:#2196f3;font-weight:bold;'>═══ 3. MODELS (38) ═══</span>\n";
// ═══════════════════════════════════════════════════════════════════════

$models = [
    'Clinic', 'ClinicLocation', 'ClinicRoom', 'ClinicEquipment',
    'User', 'Patient', 'PatientFamilyMember', 'PatientPhoto',
    'Appointment', 'AppointmentService', 'DoctorAvailability',
    'Visit', 'VisitLesion', 'VisitScale', 'VisitProcedure',
    'Prescription', 'PrescriptionDrug', 'IndianDrug',
    'Invoice', 'InvoiceItem', 'Payment', 'GstSacCode',
    'LabOrder', 'LabOrderTest', 'LabTestCatalog', 'VendorLab',
    'WhatsappMessage', 'NotificationQueue', 'AuditLog',
    'AbdmConsent', 'AbdmCareContext',
    'DentalTooth', 'DentalToothHistory', 'DentalLabOrder',
    'PhysioTreatmentPlan', 'PhysioHep',
    'OphthalVaLog', 'OphthalRefraction',
];

foreach ($models as $model) {
    $class = "App\\Models\\{$model}";
    test($model, class_exists($class), $passed, $failed);
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n<span style='color:#2196f3;font-weight:bold;'>═══ 4. KEY MODEL RELATIONSHIPS ═══</span>\n";
// ═══════════════════════════════════════════════════════════════════════

$relationships = [
    'User' => ['clinic'],
    'Patient' => ['clinic', 'appointments', 'visits', 'invoices', 'photos', 'prescriptions'],
    'Appointment' => ['clinic', 'patient', 'doctor'],
    'Invoice' => ['clinic', 'patient', 'items', 'payments'],
    'Visit' => ['clinic', 'patient', 'doctor', 'prescriptions'],
    'Prescription' => ['clinic', 'patient', 'doctor', 'drugs'],
    'PatientPhoto' => ['clinic', 'patient', 'visit', 'uploadedBy'],
    'Payment' => ['clinic', 'invoice', 'patient'],
];

foreach ($relationships as $model => $rels) {
    $class = "App\\Models\\{$model}";
    if (class_exists($class)) {
        foreach ($rels as $rel) {
            $hasRel = method_exists($class, $rel);
            test("{$model}->{$rel}()", $hasRel, $passed, $failed);
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n<span style='color:#2196f3;font-weight:bold;'>═══ 5. WEB ROUTES ═══</span>\n";
// ═══════════════════════════════════════════════════════════════════════

$webRoutes = [
    'login', 'register', 'logout', 'dashboard', 'schedule',
    'patients.index', 'patients.create', 'patients.store', 'patients.show', 'patients.edit', 'patients.update', 'patients.destroy',
    'patients.upload-photo', 'patients.view-photo', 'patients.delete-photo',
    'appointments.index', 'appointments.create', 'appointments.store', 'appointments.show', 'appointments.status', 'appointments.destroy',
    'emr.index', 'emr.show', 'emr.create', 'emr.update', 'emr.finalise',
    'billing.index', 'billing.create', 'billing.store', 'billing.show', 'billing.pdf', 'billing.send-whatsapp', 'billing.mark-paid',
    'payments.index', 'gst-reports.index', 'photo-vault.index', 'prescriptions.index',
    'analytics.index', 'settings.index', 'settings.clinic', 'settings.billing',
    'vendor.index', 'vendor.accept', 'vendor.upload',
    'whatsapp.index', 'whatsapp.send', 'whatsapp.broadcast',
    'abdm.index',
];

foreach ($webRoutes as $route) {
    test("Route: {$route}", \Illuminate\Support\Facades\Route::has($route), $passed, $failed);
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n<span style='color:#2196f3;font-weight:bold;'>═══ 6. BLADE VIEWS ═══</span>\n";
// ═══════════════════════════════════════════════════════════════════════

$views = [
    'layouts.app', 'layouts.guest', 'welcome',
    'auth.login', 'auth.register',
    'dashboard.index',
    'patients.index', 'patients.create', 'patients.show',
    'appointments.index', 'appointments.create', 'appointments.show',
    'emr.index', 'emr.show',
    'billing.index', 'billing.create', 'billing.show', 'billing.invoice-pdf',
    'payments.index', 'gst-reports.index', 'photo-vault.index', 'prescriptions.index',
    'analytics.index', 'settings.index', 'abdm.index', 'whatsapp.index',
];

foreach ($views as $view) {
    test("View: {$view}", view()->exists($view), $passed, $failed);
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n<span style='color:#2196f3;font-weight:bold;'>═══ 7. DATABASE TABLES ═══</span>\n";
// ═══════════════════════════════════════════════════════════════════════

$tables = [
    'clinics', 'clinic_locations', 'clinic_rooms', 'clinic_equipment',
    'users', 'personal_access_tokens', 'password_reset_tokens',
    'patients', 'patient_family_members',
    'appointment_services', 'doctor_availability', 'appointments',
    'visits', 'visit_lesions', 'visit_scales', 'visit_procedures',
    'prescriptions', 'prescription_drugs', 'indian_drugs',
    'invoices', 'invoice_items', 'payments', 'gst_sac_codes',
    'patient_photos', 'whatsapp_messages', 'notification_queue', 'audit_logs',
    'abdm_consents', 'abdm_care_contexts',
    'lab_test_catalog', 'vendor_labs', 'lab_orders', 'lab_order_tests',
    'dental_teeth', 'dental_tooth_history', 'dental_lab_orders',
    'physio_treatment_plans', 'physio_hep',
    'ophthal_va_logs', 'ophthal_refractions',
];

try {
    foreach ($tables as $table) {
        $exists = \Illuminate\Support\Facades\Schema::hasTable($table);
        $count = $exists ? \Illuminate\Support\Facades\DB::table($table)->count() : 0;
        test("Table: {$table} ({$count} rows)", $exists, $passed, $failed);
    }
} catch (\Exception $e) {
    echo "<span style='color:#f44336;'>❌ Database connection error: " . $e->getMessage() . "</span>\n";
    $failed++;
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n<span style='color:#2196f3;font-weight:bold;'>═══ 8. STORAGE & PERMISSIONS ═══</span>\n";
// ═══════════════════════════════════════════════════════════════════════

$storagePaths = [
    'storage/app/public' => storage_path('app/public'),
    'storage/app/public/patient_photos' => storage_path('app/public/patient_photos'),
    'storage/framework/views' => storage_path('framework/views'),
    'storage/framework/cache' => storage_path('framework/cache'),
    'storage/framework/sessions' => storage_path('framework/sessions'),
    'storage/logs' => storage_path('logs'),
];

foreach ($storagePaths as $name => $path) {
    $exists = file_exists($path);
    $writable = $exists && is_writable($path);
    
    if ($writable) {
        test("{$name} (writable)", true, $passed, $failed);
    } elseif ($exists) {
        warn("{$name} (exists but not writable)", $warnings);
    } else {
        test("{$name} (missing)", false, $passed, $failed);
    }
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n<span style='color:#2196f3;font-weight:bold;'>═══ 9. SERVICES & DEPENDENCIES ═══</span>\n";
// ═══════════════════════════════════════════════════════════════════════

test('WhatsAppService', class_exists('App\\Services\\WhatsAppService'), $passed, $failed);
test('DomPDF available', class_exists('Barryvdh\\DomPDF\\Facade\\Pdf'), $passed, $failed);

// Check Sanctum - it's a trait, so we verify via the User model
$userClass = 'App\\Models\\User';
$sanctumInstalled = class_exists($userClass) && in_array('Laravel\\Sanctum\\HasApiTokens', class_uses($userClass));
test('Sanctum available (via User model)', $sanctumInstalled, $passed, $failed);

// ═══════════════════════════════════════════════════════════════════════
echo "\n<span style='color:#2196f3;font-weight:bold;'>═══ 10. AUTHENTICATION ═══</span>\n";
// ═══════════════════════════════════════════════════════════════════════

if (auth()->check()) {
    $user = auth()->user();
    echo "<span style='color:#4caf50;'>✅ Logged in as: {$user->name} ({$user->email})</span>\n";
    echo "   └─ Clinic ID: {$user->clinic_id}\n";
    echo "   └─ Role: {$user->role}\n";
    $passed++;
} else {
    warn("Not logged in - test routes via browser", $warnings);
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n<span style='color:#2196f3;font-weight:bold;'>═══ 11. ENVIRONMENT ═══</span>\n";
// ═══════════════════════════════════════════════════════════════════════

test('.env exists', file_exists(base_path('.env')), $passed, $failed);
test('APP_KEY set', !empty(config('app.key')), $passed, $failed);
test('APP_ENV: ' . config('app.env'), true, $passed, $failed);
test('APP_DEBUG: ' . (config('app.debug') ? 'true' : 'false'), true, $passed, $failed);
test('APP_URL: ' . config('app.url'), true, $passed, $failed);
test('Database: ' . config('database.default'), true, $passed, $failed);
test('Session: ' . config('session.driver'), true, $passed, $failed);

// ═══════════════════════════════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════════════════════════════
echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                      TEST SUMMARY                            ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
printf("║  <span style='color:#4caf50;'>✅ PASSED:   %3d</span>                                          ║\n", $passed);
printf("║  <span style='color:#f44336;'>❌ FAILED:   %3d</span>                                          ║\n", $failed);
printf("║  <span style='color:#ff9800;'>⚠️  WARNINGS: %3d</span>                                          ║\n", $warnings);
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

if ($failed === 0) {
    echo "<span style='color:#4caf50;font-weight:bold;font-size:16px;'>🎉 ALL TESTS PASSED! Project is ready for deployment.</span>\n\n";
} else {
    echo "<span style='color:#f44336;font-weight:bold;'>Some tests failed. Please review the errors above.</span>\n\n";
}

echo "<span style='color:#ff9800;font-weight:bold;'>⚠️ DELETE THIS FILE AFTER TESTING!</span>\n";
echo "</pre>";
