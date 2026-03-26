<?php
/**
 * Debug login page - check what's causing 500 error
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "=== Debug Login Page ===\n\n";

require __DIR__ . '/../vendor/autoload.php';

try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "✅ Laravel bootstrapped\n\n";
} catch (\Throwable $e) {
    die("❌ Bootstrap error: " . $e->getMessage());
}

// Check if views exist
echo "Checking view files:\n";
$viewsPath = __DIR__ . '/../resources/views';
$viewFiles = [
    'layouts/app.blade.php',
    'layouts/guest.blade.php',
    'auth/login.blade.php',
    'auth/register.blade.php',
    'welcome.blade.php',
    'dashboard/index.blade.php',
];

foreach ($viewFiles as $view) {
    $fullPath = $viewsPath . '/' . $view;
    $exists = file_exists($fullPath) ? '✅' : '❌';
    echo "  {$exists} {$view}\n";
}

echo "\n";

// Check storage permissions
echo "Checking storage directories:\n";
$storageDirs = [
    __DIR__ . '/../storage',
    __DIR__ . '/../storage/logs',
    __DIR__ . '/../storage/framework',
    __DIR__ . '/../storage/framework/views',
    __DIR__ . '/../storage/framework/cache',
    __DIR__ . '/../storage/framework/sessions',
    __DIR__ . '/../bootstrap/cache',
];

foreach ($storageDirs as $dir) {
    $shortPath = str_replace(__DIR__ . '/../', '', $dir);
    if (!file_exists($dir)) {
        echo "  ❌ MISSING: {$shortPath}\n";
        // Try to create it
        if (@mkdir($dir, 0775, true)) {
            echo "     ✅ Created!\n";
        }
    } else {
        $writable = is_writable($dir) ? '✅' : '❌ NOT WRITABLE';
        echo "  {$writable} {$shortPath}\n";
    }
}

echo "\n";

// Check .env
echo "Checking .env:\n";
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    echo "  ✅ .env exists\n";
    
    // Check APP_KEY
    $envContent = file_get_contents($envPath);
    if (strpos($envContent, 'APP_KEY=base64:') !== false) {
        echo "  ✅ APP_KEY is set\n";
    } else {
        echo "  ❌ APP_KEY is NOT set! Run: php artisan key:generate\n";
    }
} else {
    echo "  ❌ .env file missing!\n";
}

echo "\n";

// Try to render the login view
echo "Attempting to render login view...\n";
try {
    $html = view('auth.login')->render();
    echo "✅ Login view rendered successfully!\n";
    echo "   HTML length: " . strlen($html) . " bytes\n";
} catch (\Throwable $e) {
    echo "❌ View render error:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n\n";
    
    // Show more context
    if (strpos($e->getMessage(), 'file_put_contents') !== false) {
        echo "   → This is a PERMISSION issue with storage/framework/views\n";
        echo "   → Fix: chmod -R 775 storage bootstrap/cache\n";
    }
}

echo "\n";

// Check Laravel logs for recent errors
echo "Recent Laravel errors:\n";
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $lastLines = array_slice($lines, -30);
    foreach ($lastLines as $line) {
        if (trim($line)) {
            echo "  " . substr($line, 0, 200) . "\n";
        }
    }
} else {
    echo "  No log file found (might be permission issue)\n";
}

echo "</pre>";
