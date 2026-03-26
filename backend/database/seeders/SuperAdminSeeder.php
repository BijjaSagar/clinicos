<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@clinicos.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'SuperAdmin@123');

        // Check if super admin already exists
        $existing = User::where('email', $email)->first();
        
        if ($existing) {
            // Update to super_admin role if not already
            if ($existing->role !== 'super_admin') {
                $existing->update(['role' => 'super_admin', 'clinic_id' => null]);
                Log::info('Existing user updated to super_admin', ['email' => $email]);
                $this->command->info("Updated existing user '{$email}' to super_admin role.");
            } else {
                $this->command->info("Super admin '{$email}' already exists.");
            }
            return;
        }

        // Create new super admin
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'super_admin',
            'clinic_id' => null, // Super admins are not tied to any clinic
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Log::info('Super admin created', ['id' => $admin->id, 'email' => $email]);
        
        $this->command->info('');
        $this->command->info('==========================================');
        $this->command->info('🔐 SUPER ADMIN CREATED SUCCESSFULLY');
        $this->command->info('==========================================');
        $this->command->info("Email: {$email}");
        $this->command->info("Password: {$password}");
        $this->command->info("Login: /admin/login");
        $this->command->info('==========================================');
        $this->command->warn('⚠️  CHANGE THE PASSWORD IMMEDIATELY!');
        $this->command->info('');
    }
}
