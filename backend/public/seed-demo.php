<?php
/**
 * Demo Data Seeder - Run once then DELETE this file!
 * Access via: https://clinic0s.com/seed-demo.php?key=clinicos2026
 */

// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Security check
$secretKey = 'clinicos2026';

if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    die('Access denied. Use: ?key=' . $secretKey);
}

echo "<pre>";
echo "=== ClinicOS Demo Seeder ===\n\n";

// Step 1: Check if autoload exists
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
echo "Checking autoload at: {$autoloadPath}\n";

if (!file_exists($autoloadPath)) {
    die("❌ ERROR: vendor/autoload.php not found!\n\nRun: composer install");
}
echo "✅ Autoload found\n\n";

// Step 2: Load autoload
echo "Loading autoload...\n";
require $autoloadPath;
echo "✅ Autoload loaded\n\n";

// Step 3: Bootstrap Laravel
echo "Bootstrapping Laravel...\n";
try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "✅ Laravel bootstrapped\n\n";
} catch (\Throwable $e) {
    die("❌ Bootstrap error: " . $e->getMessage() . "\n\nFile: " . $e->getFile() . ":" . $e->getLine());
}

// Step 4: Test database connection
echo "Testing database connection...\n";
try {
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "✅ Database connected\n\n";
} catch (\Throwable $e) {
    die("❌ Database error: " . $e->getMessage() . "\n\nCheck your .env DB_* settings");
}

// Step 5: Check if tables exist
echo "Checking tables...\n";
try {
    $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
    echo "Tables found: " . count($tables) . "\n";
    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        echo "  - {$tableName}\n";
    }
    echo "\n";
} catch (\Throwable $e) {
    die("❌ Error checking tables: " . $e->getMessage());
}

// Step 6: Create demo data
echo "Creating demo data...\n\n";

try {
    // Check if demo user already exists
    $existingUser = \App\Models\User::where('email', 'demo@clinicos.com')->first();
    if ($existingUser) {
        echo "⚠️  Demo user already exists!\n\n";
        echo "Email: demo@clinicos.com\n";
        echo "Password: password\n\n";
        echo "Login at: https://clinic0s.com/login\n";
        echo "</pre>";
        exit;
    }

    // Create clinic
    echo "Creating clinic...\n";
    $clinic = \App\Models\Clinic::create([
        'name' => 'Sharma Skin Clinic',
        'slug' => 'sharma-skin-clinic-' . \Illuminate\Support\Str::random(6),
        'plan' => 'small',
        'specialties' => ['dermatology'],
        'city' => 'Pune',
        'state' => 'Maharashtra',
        'is_active' => true,
        'settings' => ['invoice_prefix' => 'SSC'],
        'trial_ends_at' => now()->addDays(30),
    ]);
    echo "✅ Clinic created (ID: {$clinic->id})\n";

    // Create owner/admin user
    echo "Creating owner user...\n";
    $user = \App\Models\User::create([
        'clinic_id' => $clinic->id,
        'name' => 'Dr. Priya Sharma',
        'email' => 'demo@clinicos.com',
        'phone' => '+919876543210',
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
        'role' => 'owner',  // Valid values: owner, doctor, receptionist, nurse, staff, vendor_admin
        'is_active' => true,
    ]);
    echo "✅ User created (ID: {$user->id})\n";

    // Link owner
    $clinic->update(['owner_user_id' => $user->id]);

    echo "\n";
    echo "==========================================\n";
    echo "🎉 SUCCESS!\n";
    echo "==========================================\n\n";
    echo "📧 Email: demo@clinicos.com\n";
    echo "🔐 Password: password\n\n";
    echo "🔗 Login: https://clinic0s.com/login\n\n";
    echo "⚠️  DELETE THIS FILE NOW!\n";

} catch (\Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
