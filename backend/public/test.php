<?php
// Simple test file - no Laravel dependencies
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

echo "<h2>Checking paths:</h2>";
echo "<pre>";

// Current file location
echo "Current file: " . __FILE__ . "\n";
echo "Current dir: " . __DIR__ . "\n\n";

// Check parent directories
echo "Parent dir: " . dirname(__DIR__) . "\n";
echo "Two levels up: " . dirname(dirname(__DIR__)) . "\n\n";

// Check if key files exist
$files = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../bootstrap/app.php',
    __DIR__ . '/../.env',
    __DIR__ . '/../artisan',
    __DIR__ . '/../composer.json',
    dirname(__DIR__) . '/vendor/autoload.php',
];

echo "Checking files:\n";
foreach ($files as $file) {
    $exists = file_exists($file) ? '✅ EXISTS' : '❌ MISSING';
    echo "{$exists}: {$file}\n";
}

echo "\n";

// List directories in public_html
echo "Contents of parent directory:\n";
$parentDir = dirname(__DIR__);
if (is_dir($parentDir)) {
    $items = scandir($parentDir);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..') {
            $type = is_dir($parentDir . '/' . $item) ? '[DIR]' : '[FILE]';
            echo "  {$type} {$item}\n";
        }
    }
}

echo "</pre>";

echo "<h2>PHP Info (limited)</h2>";
echo "<p>Memory limit: " . ini_get('memory_limit') . "</p>";
echo "<p>Max execution time: " . ini_get('max_execution_time') . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
