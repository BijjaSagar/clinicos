<?php
/**
 * Creates a symbolic link from public/storage to storage/app/public
 * Run this once after deployment: https://clinic0s.com/create-storage-link.php?key=clinicos2026
 * DELETE THIS FILE AFTER RUNNING!
 */

$secretKey = 'clinicos2026';

if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(403);
    die('Access denied. Provide correct key.');
}

echo "<pre style='font-family: monospace; background: #1a1a2e; color: #eee; padding: 20px; border-radius: 8px;'>";
echo "=== Storage Link Creator ===\n\n";

$publicPath = __DIR__ . '/storage';
$targetPath = __DIR__ . '/../storage/app/public';

echo "Public path: $publicPath\n";
echo "Target path: $targetPath\n\n";

// Check if target exists
if (!is_dir($targetPath)) {
    echo "Creating target directory...\n";
    if (!mkdir($targetPath, 0755, true)) {
        echo "<span style='color: #ff6b6b;'>✗ Failed to create target directory</span>\n";
        echo "</pre>";
        exit(1);
    }
    echo "<span style='color: #6ee7b7;'>✓ Target directory created</span>\n";
}

// Check if link already exists
if (file_exists($publicPath)) {
    if (is_link($publicPath)) {
        echo "<span style='color: #fbbf24;'>⚠ Symbolic link already exists</span>\n";
        echo "Current target: " . readlink($publicPath) . "\n";
    } else {
        echo "<span style='color: #ff6b6b;'>✗ A file/directory already exists at public/storage</span>\n";
        echo "Please remove it manually first.\n";
    }
} else {
    // Try to create symbolic link
    echo "Creating symbolic link...\n";
    
    // Use relative path for better portability
    $relativePath = '../storage/app/public';
    
    if (symlink($relativePath, $publicPath)) {
        echo "<span style='color: #6ee7b7;'>✓ Symbolic link created successfully!</span>\n";
    } else {
        echo "<span style='color: #ff6b6b;'>✗ Failed to create symbolic link</span>\n";
        echo "\nTrying alternative method (copy approach for shared hosting)...\n";
        
        // On some shared hosts, symlinks don't work. Create .htaccess redirect instead
        $htaccessContent = "RewriteEngine On\nRewriteRule ^(.*)$ ../storage/app/public/$1 [L]\n";
        
        if (!is_dir($publicPath)) {
            mkdir($publicPath, 0755, true);
        }
        
        if (file_put_contents($publicPath . '/.htaccess', $htaccessContent)) {
            echo "<span style='color: #6ee7b7;'>✓ Created .htaccess redirect instead</span>\n";
        } else {
            echo "<span style='color: #ff6b6b;'>✗ Could not create .htaccess</span>\n";
        }
    }
}

// Create patient_photos directory if it doesn't exist
$patientPhotosPath = $targetPath . '/patient_photos';
if (!is_dir($patientPhotosPath)) {
    if (mkdir($patientPhotosPath, 0755, true)) {
        echo "\n<span style='color: #6ee7b7;'>✓ Created patient_photos directory</span>\n";
    }
}

// Test write permissions
$testFile = $patientPhotosPath . '/test_write.txt';
if (file_put_contents($testFile, 'test')) {
    unlink($testFile);
    echo "<span style='color: #6ee7b7;'>✓ Write permissions OK</span>\n";
} else {
    echo "<span style='color: #ff6b6b;'>✗ Cannot write to storage directory</span>\n";
    echo "Run: chmod -R 755 storage/app/public\n";
}

echo "\n=== Done ===\n";
echo "\n<span style='color: #ff6b6b; font-weight: bold;'>⚠️ DELETE THIS FILE NOW!</span>\n";
echo "</pre>";

echo "\n<p><a href='/patients'>→ Go to Patients</a></p>";
