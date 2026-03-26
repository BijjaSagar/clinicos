<?php
/**
 * Cleanup orphaned photo records (database entries without files)
 * Run: https://clinic0s.com/cleanup-photos.php?key=clinicos2026
 * DELETE THIS FILE AFTER RUNNING!
 */

$secretKey = 'clinicos2026';

if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(403);
    die('Access denied.');
}

echo "<pre style='font-family: monospace; background: #1a1a2e; color: #eee; padding: 20px; border-radius: 8px;'>";
echo "=== Photo Cleanup ===\n\n";

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());
    
    $storagePath = storage_path('app/public');
    echo "Storage path: $storagePath\n\n";
    
    $photos = \App\Models\PatientPhoto::all();
    $deleted = 0;
    $kept = 0;
    
    echo "Checking " . $photos->count() . " photos...\n\n";
    
    foreach ($photos as $photo) {
        $fullPath = $storagePath . '/' . $photo->s3_key;
        
        if (file_exists($fullPath)) {
            echo "<span style='color: #6ee7b7;'>✓ KEEP</span> ID:{$photo->id} - File exists\n";
            $kept++;
        } else {
            echo "<span style='color: #ff6b6b;'>✗ DELETE</span> ID:{$photo->id} - {$photo->s3_key} (file missing)\n";
            $photo->delete();
            $deleted++;
        }
    }
    
    echo "\n──────────────────────────────────────\n";
    echo "Summary:\n";
    echo "  Kept: $kept\n";
    echo "  Deleted: $deleted\n";
    
    if ($deleted > 0) {
        echo "\n<span style='color: #6ee7b7;'>✓ Orphaned records cleaned up!</span>\n";
    }
    
} catch (Exception $e) {
    echo "<span style='color: #ff6b6b;'>✗ Error: " . $e->getMessage() . "</span>\n";
}

echo "\n=== Done ===\n";
echo "\n<span style='color: #ff6b6b; font-weight: bold;'>⚠️ DELETE THIS FILE!</span>\n";
echo "</pre>";

echo "\n<p><a href='/patients/1'>→ Go to Patient #1 Profile</a></p>";
echo "<p><a href='/photo-vault'>→ Go to Photo Vault</a></p>";
