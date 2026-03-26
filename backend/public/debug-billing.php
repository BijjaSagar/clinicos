<?php
/**
 * Debug Billing - Check if visit_id is being passed
 * DELETE THIS FILE AFTER USE!
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Billing Debug</h2>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;}</style>";

// Check URL parameters
echo "<h3>1. URL Parameters:</h3>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h3>2. visit_id value:</h3>";
$visitId = $_GET['visit_id'] ?? 'NOT SET';
echo "<p>visit_id = <strong>" . htmlspecialchars($visitId) . "</strong></p>";

// Check if file was updated
echo "<h3>3. File Check:</h3>";
$billingCreateFile = __DIR__ . '/../resources/views/billing/create.blade.php';
if (file_exists($billingCreateFile)) {
    $content = file_get_contents($billingCreateFile);
    
    // Check for the debug message
    if (strpos($content, 'Linking to Visit') !== false) {
        echo "<p style='color:green'>✅ billing/create.blade.php has the debug message (updated)</p>";
    } else {
        echo "<p style='color:red'>❌ billing/create.blade.php does NOT have the debug message (old version)</p>";
    }
    
    // Check for hidden visit_id field
    if (strpos($content, "name=\"visit_id\"") !== false) {
        echo "<p style='color:green'>✅ Has visit_id hidden field</p>";
    } else {
        echo "<p style='color:red'>❌ Missing visit_id hidden field</p>";
    }
} else {
    echo "<p style='color:red'>❌ File not found!</p>";
}

// Check controller
$controllerFile = __DIR__ . '/../app/Http/Controllers/Web/BillingWebController.php';
if (file_exists($controllerFile)) {
    $content = file_get_contents($controllerFile);
    
    if (strpos($content, "'visit_id' => \$visitId") !== false) {
        echo "<p style='color:green'>✅ BillingWebController has visit_id in create</p>";
    } else {
        echo "<p style='color:red'>❌ BillingWebController missing visit_id in create</p>";
    }
} else {
    echo "<p style='color:red'>❌ Controller file not found!</p>";
}

// Test link
echo "<h3>4. Test Links:</h3>";
echo "<p><a href='/billing/create?patient_id=1&visit_id=3'>Test: /billing/create?patient_id=1&visit_id=3</a></p>";

// Git status
echo "<h3>5. Git Info:</h3>";
$gitDir = __DIR__ . '/../.git';
if (is_dir($gitDir)) {
    echo "<p>Git directory exists</p>";
    
    // Try to get current commit
    $headFile = $gitDir . '/refs/heads/main';
    if (file_exists($headFile)) {
        $commit = trim(file_get_contents($headFile));
        echo "<p>Current commit: <code>" . substr($commit, 0, 8) . "</code></p>";
    }
} else {
    echo "<p style='color:orange'>No .git directory found (might be in parent)</p>";
}

echo "<hr><p style='color:red;font-weight:bold'>⚠️ DELETE THIS FILE AFTER DEBUGGING!</p>";
