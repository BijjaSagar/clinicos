<?php
/**
 * Debug script to check photo storage
 * Run: https://clinic0s.com/check-photos.php?key=clinicos2026
 * DELETE THIS FILE AFTER DEBUGGING!
 */

$secretKey = 'clinicos2026';

if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(403);
    die('Access denied.');
}

echo "<pre style='font-family: monospace; background: #1a1a2e; color: #eee; padding: 20px; border-radius: 8px;'>";
echo "=== Photo Storage Debug ===\n\n";

// 1. Check paths
echo "1. PATH CHECKS\n";
echo "──────────────────────────────────────\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Public Dir: " . __DIR__ . "\n";

$storagePath = realpath(__DIR__ . '/../storage/app/public');
if (!$storagePath) {
    $storagePath = __DIR__ . '/../storage/app/public';
}

echo "Storage Path: $storagePath\n";
echo "Real Storage Path: " . realpath($storagePath) . "\n\n";

// 2. Check if storage/app/public exists
echo "2. STORAGE DIRECTORY\n";
echo "──────────────────────────────────────\n";
if (is_dir($storagePath)) {
    echo "<span style='color: #6ee7b7;'>✓ storage/app/public exists</span>\n";
    echo "Permissions: " . substr(sprintf('%o', fileperms($storagePath)), -4) . "\n";
    echo "Writable: " . (is_writable($storagePath) ? 'Yes' : 'No') . "\n";
} else {
    echo "<span style='color: #ff6b6b;'>✗ storage/app/public does NOT exist</span>\n";
    echo "Creating it now...\n";
    if (mkdir($storagePath, 0755, true)) {
        echo "<span style='color: #6ee7b7;'>✓ Created successfully</span>\n";
    } else {
        echo "<span style='color: #ff6b6b;'>✗ Failed to create</span>\n";
    }
}

// 3. Check patient_photos directory
echo "\n3. PATIENT PHOTOS DIRECTORY\n";
echo "──────────────────────────────────────\n";
$patientPhotosPath = $storagePath . '/patient_photos';
if (is_dir($patientPhotosPath)) {
    echo "<span style='color: #6ee7b7;'>✓ patient_photos directory exists</span>\n";
    
    // Recursively list all files
    echo "Scanning for files...\n";
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($patientPhotosPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $fileCount = 0;
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relativePath = str_replace($storagePath . '/', '', $file->getPathname());
            echo "  📄 $relativePath (" . round($file->getSize() / 1024) . " KB)\n";
            $fileCount++;
        }
    }
    
    if ($fileCount === 0) {
        echo "  (no files found)\n";
    } else {
        echo "\nTotal files: $fileCount\n";
    }
} else {
    echo "<span style='color: #fbbf24;'>⚠ patient_photos directory does not exist</span>\n";
    echo "Creating it...\n";
    if (mkdir($patientPhotosPath, 0755, true)) {
        echo "<span style='color: #6ee7b7;'>✓ Created</span>\n";
    }
}

// 4. Check database for photos
echo "\n4. DATABASE PHOTOS\n";
echo "──────────────────────────────────────\n";
try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());
    
    $photos = \App\Models\PatientPhoto::latest()->limit(10)->get();
    
    if ($photos->count() > 0) {
        echo "Photos in database: {$photos->count()}\n\n";
        foreach ($photos as $photo) {
            echo "ID: {$photo->id} | Patient: {$photo->patient_id} | Type: {$photo->photo_type}\n";
            echo "  s3_key: {$photo->s3_key}\n";
            
            // Check if file exists
            $fullPath = $storagePath . '/' . $photo->s3_key;
            if (file_exists($fullPath)) {
                echo "  <span style='color: #6ee7b7;'>✓ File EXISTS</span> (" . round(filesize($fullPath) / 1024) . " KB)\n";
            } else {
                echo "  <span style='color: #ff6b6b;'>✗ File NOT found</span>\n";
                echo "  Expected at: $fullPath\n";
            }
            
            // Show the route URL (this will work without symlink)
            echo "  View URL: /patients/{$photo->patient_id}/photos/{$photo->id}\n\n";
        }
    } else {
        echo "<span style='color: #fbbf24;'>No photos in database yet.</span>\n";
        echo "Try uploading a photo from a patient's profile first.\n";
    }
} catch (Exception $e) {
    echo "<span style='color: #ff6b6b;'>✗ Error: " . $e->getMessage() . "</span>\n";
}

// 5. Symlink info (for reference)
echo "\n5. SYMLINK STATUS\n";
echo "──────────────────────────────────────\n";
echo "<span style='color: #fbbf24;'>⚠ Symlinks are not supported on this hosting.</span>\n";
echo "Photos are served via secure Laravel route instead:\n";
echo "  /patients/{patient_id}/photos/{photo_id}\n";
echo "\nThis is actually MORE secure because:\n";
echo "  - Only authenticated users can view photos\n";
echo "  - Users can only view photos from their own clinic\n";

echo "\n=== End Debug ===\n";
echo "\n<span style='color: #ff6b6b; font-weight: bold;'>⚠️ DELETE THIS FILE!</span>\n";
echo "</pre>";

echo "\n<p>Quick links:</p>";
echo "<ul>";
echo "<li><a href='/patients'>→ Go to Patients</a></li>";
echo "<li><a href='/photo-vault'>→ Go to Photo Vault</a></li>";
echo "</ul>";
