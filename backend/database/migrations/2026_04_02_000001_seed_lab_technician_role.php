<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add lab_technician to the users.role enum column.
 * Replaces the Spatie-based role seeder (project uses native DB enum, not Spatie).
 */
return new class extends Migration
{
    public function up(): void
    {
        // MariaDB/MySQL: Modify the enum to include lab_technician
        $current = DB::select("SHOW COLUMNS FROM users LIKE 'role'");
        if (empty($current)) {
            return;
        }

        $typeStr = $current[0]->Type ?? $current[0]->type ?? '';
        // Only add if not already there
        if (str_contains($typeStr, 'lab_technician')) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'super_admin','owner','doctor','receptionist','nurse','staff',
            'vendor_admin','lab_technician','pharmacist'
        ) NOT NULL DEFAULT 'staff'");
    }

    public function down(): void
    {
        // Revert: remove lab_technician and pharmacist from enum
        // Only safe if no users have those roles
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'super_admin','owner','doctor','receptionist','nurse','staff','vendor_admin'
        ) NOT NULL DEFAULT 'staff'");
    }
};
