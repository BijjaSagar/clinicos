<?php
/**
 * Debug dashboard page
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$_SERVER['APP_DEBUG'] = 'true';
$_ENV['APP_DEBUG'] = 'true';
putenv('APP_DEBUG=true');

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test dashboard page
$_SERVER['REQUEST_URI'] = '/dashboard';
$_SERVER['REQUEST_METHOD'] = 'GET';
$request = Illuminate\Http\Request::capture();

try {
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
    
} catch (\Throwable $e) {
    echo "<h1>Exception Caught</h1>";
    echo "<pre>";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
