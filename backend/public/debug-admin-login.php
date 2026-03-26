<?php
/**
 * Debug Admin Login
 * DELETE THIS FILE AFTER USE!
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(\Illuminate\Http\Request::capture());

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "<h2>Admin Login Debug</h2>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;max-width:800px;margin:0 auto}code{background:#f0f0f0;padding:2px 6px;border-radius:4px}</style>";

$email = 'superadmin@clinicos.com';
$password = 'password';

echo "<h3>1. Looking for user: <code>{$email}</code></h3>";

$user = User::where('email', $email)->first();

if (!$user) {
    echo "<p style='color:red'>❌ User NOT FOUND in database!</p>";
    echo "<p>Run this SQL to create:</p>";
    echo "<pre>INSERT INTO users (name, email, password, role, clinic_id, is_active, email_verified_at, created_at, updated_at)
VALUES ('Super Admin', 'superadmin@clinicos.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 3, 1, NOW(), NOW(), NOW());</pre>";
    exit;
}

echo "<p style='color:green'>✅ User found!</p>";

echo "<h3>2. User Details:</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse:collapse'>";
echo "<tr><td><strong>ID</strong></td><td>{$user->id}</td></tr>";
echo "<tr><td><strong>Name</strong></td><td>{$user->name}</td></tr>";
echo "<tr><td><strong>Email</strong></td><td>{$user->email}</td></tr>";
echo "<tr><td><strong>Role</strong></td><td><code>" . ($user->role ?? 'NULL/EMPTY') . "</code></td></tr>";
echo "<tr><td><strong>Role === 'super_admin'?</strong></td><td>" . ($user->role === 'super_admin' ? '✅ YES' : '❌ NO') . "</td></tr>";
echo "<tr><td><strong>Clinic ID</strong></td><td>" . ($user->clinic_id ?? 'NULL') . "</td></tr>";
echo "<tr><td><strong>Is Active</strong></td><td>" . ($user->is_active ? 'Yes' : 'No') . "</td></tr>";
echo "<tr><td><strong>Password Hash</strong></td><td><code style='word-break:break-all'>" . substr($user->password, 0, 30) . "...</code></td></tr>";
echo "</table>";

echo "<h3>3. Password Check:</h3>";
$passwordMatches = Hash::check($password, $user->password);
if ($passwordMatches) {
    echo "<p style='color:green'>✅ Password '<code>{$password}</code>' is CORRECT</p>";
} else {
    echo "<p style='color:red'>❌ Password '<code>{$password}</code>' is WRONG</p>";
    echo "<p>Run this SQL to fix password:</p>";
    echo "<pre>UPDATE users SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'superadmin@clinicos.com';</pre>";
}

echo "<h3>4. Role Check:</h3>";
if ($user->role === 'super_admin') {
    echo "<p style='color:green'>✅ Role is 'super_admin' - LOGIN SHOULD WORK</p>";
} else {
    echo "<p style='color:red'>❌ Role is NOT 'super_admin' - this is why login fails!</p>";
    echo "<p>Current role value: <code>" . var_export($user->role, true) . "</code></p>";
    echo "<p>Run this SQL to fix:</p>";
    echo "<pre>UPDATE users SET role = 'super_admin' WHERE email = 'superadmin@clinicos.com';</pre>";
}

echo "<h3>5. Fix All Issues:</h3>";
echo "<p>Run this single SQL to fix everything:</p>";
echo "<pre>UPDATE users SET 
    role = 'super_admin',
    password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    is_active = 1
WHERE email = 'superadmin@clinicos.com';</pre>";

echo "<hr><p style='color:red;font-weight:bold'>⚠️ DELETE THIS FILE AFTER DEBUGGING!</p>";
