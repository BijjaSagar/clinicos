<?php
/**
 * Fix session and test actual Laravel request
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "=== Fix & Test Laravel ===\n\n";

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Clear all caches
echo "1. Clearing caches...\n";
try {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    echo "   ✅ Config cleared\n";
} catch (\Throwable $e) {
    echo "   ⚠️  " . $e->getMessage() . "\n";
}

try {
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "   ✅ Views cleared\n";
} catch (\Throwable $e) {
    echo "   ⚠️  " . $e->getMessage() . "\n";
}

try {
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    echo "   ✅ Routes cleared\n";
} catch (\Throwable $e) {
    echo "   ⚠️  " . $e->getMessage() . "\n";
}

try {
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    echo "   ✅ Cache cleared\n";
} catch (\Throwable $e) {
    echo "   ⚠️  " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Check session configuration
echo "2. Session configuration:\n";
echo "   Driver: " . config('session.driver') . "\n";
echo "   Path: " . config('session.files') . "\n";

$sessionPath = config('session.files');
if ($sessionPath && !is_dir($sessionPath)) {
    mkdir($sessionPath, 0775, true);
    echo "   ✅ Created session directory\n";
}

if ($sessionPath && is_writable($sessionPath)) {
    echo "   ✅ Session path is writable\n";
} else {
    echo "   ❌ Session path NOT writable\n";
}

echo "\n";

// 3. Test making an actual HTTP request through Laravel
echo "3. Testing HTTP kernel...\n";
try {
    $httpKernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    
    // Create a fake request to /login
    $request = \Illuminate\Http\Request::create('/login', 'GET');
    
    // Handle it through the full middleware stack
    $response = $httpKernel->handle($request);
    
    $statusCode = $response->getStatusCode();
    echo "   Response status: {$statusCode}\n";
    
    if ($statusCode === 200) {
        echo "   ✅ Login page works!\n";
        echo "\n   Content length: " . strlen($response->getContent()) . " bytes\n";
    } else {
        echo "   ❌ Unexpected status code\n";
        echo "\n   Response:\n";
        echo substr($response->getContent(), 0, 1000);
    }
    
} catch (\Throwable $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n\n";
echo "==========================================\n";
echo "If all checks passed, try visiting:\n";
echo "https://clinic0s.com/login\n";
echo "==========================================\n";
echo "</pre>";
