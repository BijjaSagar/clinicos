<?php
/**
 * Debug authentication and session issues
 * Access: https://clinic0s.com/check-auth.php
 * DELETE THIS FILE AFTER DEBUGGING
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre style='font-family: monospace; background: #1a1a2e; color: #eee; padding: 20px; border-radius: 8px;'>";
echo "=== ClinicOS Auth & Session Debug ===\n\n";

// Check session config
echo "1. PHP SESSION CONFIG\n";
echo "   Session Save Path: " . session_save_path() . "\n";
echo "   Session Name: " . session_name() . "\n";

// Load Laravel and FULLY boot it
echo "\n2. LOADING LARAVEL...\n";

try {
    require __DIR__ . '/../vendor/autoload.php';
    echo "   ✓ Autoload loaded\n";
    
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "   ✓ App bootstrapped\n";
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "   ✓ Kernel created\n";
    
    // Create and handle a request to fully boot Laravel
    $request = Illuminate\Http\Request::capture();
    $response = $kernel->handle($request);
    echo "   ✓ Request handled - Laravel fully booted\n";
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "</pre>";
    exit;
}

// Now we can safely use Laravel functions
echo "\n3. LARAVEL CONFIG\n";
try {
    echo "   Session Driver: " . config('session.driver') . "\n";
    echo "   Session Lifetime: " . config('session.lifetime') . " minutes\n";
    echo "   Session Domain: " . (config('session.domain') ?: '(not set)') . "\n";
    echo "   Session Path: " . config('session.path') . "\n";
    echo "   APP_URL: " . config('app.url') . "\n";
    echo "   APP_ENV: " . config('app.env') . "\n";
} catch (Exception $e) {
    echo "   ✗ Config Error: " . $e->getMessage() . "\n";
}

// Check storage permissions
echo "\n4. STORAGE PERMISSIONS\n";
try {
    $sessionPath = storage_path('framework/sessions');
    echo "   Sessions Dir: " . $sessionPath . "\n";
    echo "   Exists: " . (is_dir($sessionPath) ? 'Yes' : 'NO - PROBLEM!') . "\n";
    if (is_dir($sessionPath)) {
        echo "   Writable: " . (is_writable($sessionPath) ? 'Yes' : 'NO - PROBLEM!') . "\n";
        $files = glob($sessionPath . '/*');
        echo "   Session Files: " . count($files) . "\n";
    }
    
    $viewsPath = storage_path('framework/views');
    echo "   Views Cache Dir: " . $viewsPath . "\n";
    echo "   Exists: " . (is_dir($viewsPath) ? 'Yes' : 'NO - PROBLEM!') . "\n";
    if (is_dir($viewsPath)) {
        echo "   Writable: " . (is_writable($viewsPath) ? 'Yes' : 'NO - PROBLEM!') . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Storage Error: " . $e->getMessage() . "\n";
}

// Check if user is authenticated
echo "\n5. AUTHENTICATION STATE\n";
try {
    $user = auth()->user();
    if ($user) {
        echo "   ✓ Logged In: YES\n";
        echo "   User ID: " . $user->id . "\n";
        echo "   User Name: " . $user->name . "\n";
        echo "   User Email: " . $user->email . "\n";
        echo "   Clinic ID: " . $user->clinic_id . "\n";
    } else {
        echo "   ✗ Logged In: NO\n";
        echo "   (This is expected if you're viewing this page directly)\n";
    }
} catch (Exception $e) {
    echo "   ✗ Auth Error: " . $e->getMessage() . "\n";
}

// Check routes
echo "\n6. ROUTE CHECK\n";
$routes = ['billing.index', 'vendor.index', 'analytics.index', 'emr.index', 'abdm.index', 'dashboard', 'schedule'];
foreach ($routes as $routeName) {
    try {
        $exists = \Illuminate\Support\Facades\Route::has($routeName);
        echo "   " . str_pad($routeName, 20) . ": " . ($exists ? '✓ Exists' : '✗ MISSING') . "\n";
    } catch (Exception $e) {
        echo "   " . str_pad($routeName, 20) . ": ✗ Error - " . $e->getMessage() . "\n";
    }
}

// Check controller files
echo "\n7. CONTROLLER FILES\n";
$controllers = [
    'BillingWebController' => base_path('app/Http/Controllers/Web/BillingWebController.php'),
    'VendorWebController' => base_path('app/Http/Controllers/Web/VendorWebController.php'),
    'AnalyticsWebController' => base_path('app/Http/Controllers/Web/AnalyticsWebController.php'),
    'EmrWebController' => base_path('app/Http/Controllers/Web/EmrWebController.php'),
];
foreach ($controllers as $name => $path) {
    echo "   " . str_pad($name, 25) . ": " . (file_exists($path) ? '✓ Exists' : '✗ MISSING') . "\n";
}

// Check view files
echo "\n8. VIEW FILES\n";
$views = [
    'billing/index' => base_path('resources/views/billing/index.blade.php'),
    'vendor/index' => base_path('resources/views/vendor/index.blade.php'),
    'analytics/index' => base_path('resources/views/analytics/index.blade.php'),
    'emr/index' => base_path('resources/views/emr/index.blade.php'),
    'abdm/index' => base_path('resources/views/abdm/index.blade.php'),
    'layouts/app' => base_path('resources/views/layouts/app.blade.php'),
];
foreach ($views as $name => $path) {
    echo "   " . str_pad($name, 20) . ": " . (file_exists($path) ? '✓ Exists' : '✗ MISSING') . "\n";
}

// Check database connection
echo "\n9. DATABASE CHECK\n";
try {
    $pdo = \DB::connection()->getPdo();
    echo "   ✓ Database Connected\n";
    echo "   Driver: " . \DB::connection()->getDriverName() . "\n";
    
    // Count users
    $userCount = \App\Models\User::count();
    echo "   Users in DB: " . $userCount . "\n";
    
    $clinicCount = \App\Models\Clinic::count();
    echo "   Clinics in DB: " . $clinicCount . "\n";
} catch (Exception $e) {
    echo "   ✗ DB Error: " . $e->getMessage() . "\n";
}

// Terminate kernel
$kernel->terminate($request, $response);

echo "\n=== END DEBUG ===\n";
echo "\n<span style='color: #ff6b6b; font-weight: bold;'>⚠️ DELETE THIS FILE AFTER DEBUGGING!</span>\n";
echo "</pre>";
