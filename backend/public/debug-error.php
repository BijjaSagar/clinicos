<?php
/**
 * Deep debug - catch the actual error
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "=== Deep Debug ===\n\n";

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Force debug mode temporarily
config(['app.debug' => true]);

echo "1. Checking web middleware...\n";

// Check if web middleware group exists
$router = app('router');
$middlewareGroups = $router->getMiddlewareGroups();

echo "   Middleware groups: " . implode(', ', array_keys($middlewareGroups)) . "\n";

if (isset($middlewareGroups['web'])) {
    echo "   Web middleware:\n";
    foreach ($middlewareGroups['web'] as $m) {
        $exists = class_exists($m) ? '✅' : '❌';
        echo "      {$exists} {$m}\n";
    }
}

echo "\n2. Testing route resolution...\n";

try {
    $routes = $router->getRoutes();
    $loginRoute = $routes->getByName('login');
    
    if ($loginRoute) {
        echo "   ✅ Login route found\n";
        echo "   URI: " . $loginRoute->uri() . "\n";
        echo "   Action: " . ($loginRoute->getActionName()) . "\n";
        echo "   Middleware: " . implode(', ', $loginRoute->middleware()) . "\n";
    } else {
        echo "   ❌ Login route not found!\n";
    }
} catch (\Throwable $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing full request with exception capture...\n";

try {
    $httpKernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $request = \Illuminate\Http\Request::create('/login', 'GET');
    
    // Set up exception handling to catch the actual error
    $app->singleton(
        \Illuminate\Contracts\Debug\ExceptionHandler::class,
        function ($app) {
            return new class extends \Illuminate\Foundation\Exceptions\Handler {
                public function render($request, \Throwable $e) {
                    throw $e; // Re-throw to catch in our try/catch
                }
            };
        }
    );
    
    $response = $httpKernel->handle($request);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    
} catch (\Throwable $e) {
    echo "   ❌ CAUGHT EXCEPTION:\n\n";
    echo "   Type: " . get_class($e) . "\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n\n";
    
    echo "   Stack trace (last 10):\n";
    $trace = $e->getTrace();
    $trace = array_slice($trace, 0, 10);
    foreach ($trace as $i => $t) {
        $file = isset($t['file']) ? basename($t['file']) : 'unknown';
        $line = $t['line'] ?? '?';
        $func = $t['function'] ?? 'unknown';
        $class = isset($t['class']) ? $t['class'] . '::' : '';
        echo "   #{$i} {$file}:{$line} {$class}{$func}()\n";
    }
    
    // Check if it's a view error and show more context
    if ($e instanceof \Illuminate\View\ViewException || strpos($e->getMessage(), 'View') !== false) {
        echo "\n   This is a VIEW error.\n";
        
        // Check previous exception
        $prev = $e->getPrevious();
        if ($prev) {
            echo "\n   Previous exception:\n";
            echo "   Type: " . get_class($prev) . "\n";
            echo "   Message: " . $prev->getMessage() . "\n";
            echo "   File: " . $prev->getFile() . "\n";
            echo "   Line: " . $prev->getLine() . "\n";
        }
    }
}

echo "\n\n4. Checking Kernel middleware...\n";

try {
    $kernelClass = new \ReflectionClass(\App\Http\Kernel::class);
    echo "   ✅ App\\Http\\Kernel exists\n";
} catch (\Throwable $e) {
    echo "   ❌ App\\Http\\Kernel not found - using default\n";
    
    // Check if middleware classes exist
    $defaultMiddleware = [
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];
    
    foreach ($defaultMiddleware as $m) {
        $exists = class_exists($m) ? '✅' : '❌';
        echo "   {$exists} " . basename(str_replace('\\', '/', $m)) . "\n";
    }
}

echo "\n</pre>";
