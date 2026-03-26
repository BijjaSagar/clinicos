<?php
/**
 * Create Super Admin User
 * Run this once to create the super admin account
 * DELETE THIS FILE AFTER RUNNING!
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>\n";
echo "=== Create Super Admin ===\n\n";

// Load Laravel
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("ERROR: vendor/autoload.php not found. Run composer install.\n");
}
require_once $autoloadPath;

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Boot the application
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

// Configuration - CHANGE THESE!
$email = 'admin@clinicos.com';
$password = 'SuperAdmin@123';
$name = 'Super Admin';

try {
    // Check if super admin already exists
    $existing = User::where('email', $email)->first();
    
    if ($existing) {
        if ($existing->role === 'super_admin') {
            echo "✓ Super admin already exists: {$email}\n";
            echo "\nLogin URL: https://clinic0s.com/admin/login\n";
        } else {
            // Update to super_admin
            $existing->update([
                'role' => 'super_admin',
                'clinic_id' => null,
                'is_active' => true,
            ]);
            echo "✓ Updated existing user to super_admin: {$email}\n";
            echo "\nLogin URL: https://clinic0s.com/admin/login\n";
        }
    } else {
        // Create new super admin
        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'super_admin',
            'clinic_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Log::info('Super admin created via web script', ['id' => $admin->id, 'email' => $email]);

        echo "==========================================\n";
        echo "🔐 SUPER ADMIN CREATED SUCCESSFULLY\n";
        echo "==========================================\n";
        echo "Email: {$email}\n";
        echo "Password: {$password}\n";
        echo "Login: https://clinic0s.com/admin/login\n";
        echo "==========================================\n\n";
        echo "⚠️  CHANGE THE PASSWORD IMMEDIATELY!\n";
    }

    echo "\n\n⚠️ DELETE THIS FILE NOW!\n";
    echo "rm -f /path/to/public/create-super-admin.php\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "</pre>";
