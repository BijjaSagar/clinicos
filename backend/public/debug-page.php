<?php
/**
 * Debug specific page errors with full error details
 * Access: https://clinic0s.com/debug-page.php?page=billing
 * DELETE THIS FILE AFTER DEBUGGING
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force debug mode
$_ENV['APP_DEBUG'] = 'true';
$_SERVER['APP_DEBUG'] = 'true';
putenv('APP_DEBUG=true');

$page = $_GET['page'] ?? 'billing';

echo "<pre style='font-family: monospace; background: #1a1a2e; color: #eee; padding: 20px; border-radius: 8px; white-space: pre-wrap; word-wrap: break-word;'>";
echo "=== Debug Page: /$page ===\n\n";

// Custom error handler to catch all errors
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require __DIR__ . '/../vendor/autoload.php';
    echo "✓ Autoload loaded\n";
    
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "✓ App bootstrapped\n";
    
    // Register custom exception handler for this request
    $app->singleton(
        Illuminate\Contracts\Debug\ExceptionHandler::class,
        function ($app) {
            return new class($app) extends Illuminate\Foundation\Exceptions\Handler {
                public function render($request, Throwable $e) {
                    throw $e; // Re-throw to our catch block
                }
            };
        }
    );
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✓ Kernel created\n\n";
    
    // Set the request URI
    $_SERVER['REQUEST_URI'] = '/' . $page;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    $request = Illuminate\Http\Request::capture();
    echo "Testing: " . $request->getRequestUri() . "\n\n";
    
    $response = $kernel->handle($request);
    
    $statusCode = $response->getStatusCode();
    echo "Status Code: $statusCode\n";
    
    if ($statusCode >= 200 && $statusCode < 300) {
        echo "\n<span style='color: #4ade80;'>✓ Page loaded successfully!</span>\n";
        echo "Content length: " . strlen($response->getContent()) . " bytes\n";
    } elseif ($statusCode === 302 || $statusCode === 301) {
        echo "\n<span style='color: #fbbf24;'>→ Redirect detected</span>\n";
        echo "Redirect to: " . $response->headers->get('Location') . "\n";
        
        // Check if redirecting to login
        if (strpos($response->headers->get('Location'), 'login') !== false) {
            echo "\n<span style='color: #ff6b6b;'>⚠ Redirecting to login - authentication issue!</span>\n";
            echo "\nChecking auth state...\n";
            
            // Check if user is authenticated
            try {
                $user = auth()->user();
                if ($user) {
                    echo "User IS authenticated: " . $user->email . "\n";
                    echo "But request is being redirected to login anyway.\n";
                    echo "This might be a middleware or session issue.\n";
                } else {
                    echo "User is NOT authenticated in this request context.\n";
                    echo "Session might not be shared with debug script.\n";
                }
            } catch (\Exception $e) {
                echo "Auth check error: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "\n<span style='color: #ff6b6b;'>✗ Error response</span>\n";
    }
    
    $kernel->terminate($request, $response);
    
} catch (\Throwable $e) {
    echo "\n<span style='color: #ff6b6b; font-size: 18px;'>✗ EXCEPTION CAUGHT</span>\n\n";
    echo "<span style='color: #fbbf24;'>Type:</span> " . get_class($e) . "\n";
    echo "<span style='color: #fbbf24;'>Message:</span> " . $e->getMessage() . "\n";
    echo "<span style='color: #fbbf24;'>File:</span> " . $e->getFile() . "\n";
    echo "<span style='color: #fbbf24;'>Line:</span> " . $e->getLine() . "\n";
    
    // Show previous exception if exists
    if ($e->getPrevious()) {
        $prev = $e->getPrevious();
        echo "\n<span style='color: #f87171;'>--- Previous Exception ---</span>\n";
        echo "Type: " . get_class($prev) . "\n";
        echo "Message: " . $prev->getMessage() . "\n";
        echo "File: " . $prev->getFile() . "\n";
        echo "Line: " . $prev->getLine() . "\n";
    }
    
    echo "\n<span style='color: #fbbf24;'>--- Stack Trace (first 20 frames) ---</span>\n";
    $trace = $e->getTrace();
    $basePath = '/home/u618910819/domains/clinic0s.com/public_html/';
    
    foreach (array_slice($trace, 0, 20) as $i => $frame) {
        $file = isset($frame['file']) ? str_replace($basePath, '', $frame['file']) : '[internal]';
        $line = $frame['line'] ?? '?';
        $class = $frame['class'] ?? '';
        $type = $frame['type'] ?? '';
        $func = $frame['function'] ?? '';
        
        echo "#$i $file:$line\n";
        echo "   $class$type$func()\n";
    }
}

echo "\n\n=== END DEBUG ===\n";
echo "</pre>";

echo "\n<p>Test other pages:</p>";
echo "<ul style='list-style: none; padding: 0;'>";
$pages = ['billing', 'analytics', 'vendor', 'emr', 'abdm', 'dashboard', 'whatsapp', 'patients', 'settings'];
foreach ($pages as $p) {
    $style = $p === $page ? 'color: #4ade80; font-weight: bold;' : 'color: #60a5fa;';
    echo "<li><a href='?page=$p' style='$style'>$p</a></li>";
}
echo "</ul>";
