<?php
/**
 * Clear all Laravel caches
 * Access: https://clinic0s.com/clear-cache.php?key=clinicos2026
 * DELETE THIS FILE AFTER USE
 */

$secretKey = 'clinicos2026';

if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    die('Access denied. Use: ?key=' . $secretKey);
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre style='font-family: monospace; background: #1a1a2e; color: #eee; padding: 20px; border-radius: 8px;'>";
echo "=== ClinicOS Cache Clear ===\n\n";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Clear view cache
echo "1. Clearing view cache...\n";
$viewPath = storage_path('framework/views');
$files = glob($viewPath . '/*');
$count = 0;
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
        $count++;
    }
}
echo "   ✓ Deleted $count compiled view files\n";

// Clear route cache
echo "\n2. Clearing route cache...\n";
$routeCachePath = base_path('bootstrap/cache/routes-v7.php');
if (file_exists($routeCachePath)) {
    unlink($routeCachePath);
    echo "   ✓ Route cache cleared\n";
} else {
    echo "   (no route cache file found)\n";
}

// Clear config cache
echo "\n3. Clearing config cache...\n";
$configCachePath = base_path('bootstrap/cache/config.php');
if (file_exists($configCachePath)) {
    unlink($configCachePath);
    echo "   ✓ Config cache cleared\n";
} else {
    echo "   (no config cache file found)\n";
}

// Clear application cache
echo "\n4. Clearing application cache...\n";
try {
    Illuminate\Support\Facades\Cache::flush();
    echo "   ✓ Application cache cleared\n";
} catch (Exception $e) {
    echo "   (cache driver not available: " . $e->getMessage() . ")\n";
}

// Clear OPcache if available
echo "\n5. Clearing OPcache...\n";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "   ✓ OPcache cleared\n";
} else {
    echo "   (OPcache not available)\n";
}

// Verify routes are registered
echo "\n6. Verifying routes...\n";
$routes = ['billing.index', 'vendor.index', 'analytics.index', 'emr.index', 'abdm.index'];
foreach ($routes as $routeName) {
    $exists = \Illuminate\Support\Facades\Route::has($routeName);
    $url = $exists ? route($routeName) : 'N/A';
    echo "   " . str_pad($routeName, 20) . ": " . ($exists ? "✓ $url" : "✗ MISSING") . "\n";
}

echo "\n=== CACHE CLEARED SUCCESSFULLY ===\n";
echo "\n<span style='color: #4ade80;'>Now try accessing the pages again!</span>\n";
echo "\n<span style='color: #ff6b6b; font-weight: bold;'>⚠️ DELETE THIS FILE AFTER USE!</span>\n";
echo "</pre>";

echo "\n\n<p><a href='/dashboard' style='color: #60a5fa;'>→ Go to Dashboard</a></p>";
echo "<p><a href='/billing' style='color: #60a5fa;'>→ Go to Billing</a></p>";
echo "<p><a href='/analytics' style='color: #60a5fa;'>→ Go to Analytics</a></p>";
echo "<p><a href='/vendor' style='color: #60a5fa;'>→ Go to Lab Orders</a></p>";
