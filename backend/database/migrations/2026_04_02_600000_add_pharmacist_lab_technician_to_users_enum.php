<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extend users.role enum to include pharmacist and lab_technician.
 * Previous migrations (000001, 000002) used Spatie which is not installed.
 * This migration uses raw DDL which works on MariaDB/MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::select("SHOW COLUMNS FROM users LIKE 'role'");
        if (empty($rows)) {
            return;
        }

        $typeStr = $rows[0]->Type ?? $rows[0]->type ?? '';

        // Only alter if one or both new values are missing
        if (str_contains($typeStr, 'pharmacist') && str_contains($typeStr, 'lab_technician')) {
            return; // Already up to date
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN `role` ENUM(
            'super_admin','owner','doctor','receptionist','nurse','staff',
            'vendor_admin','lab_technician','pharmacist'
        ) NOT NULL DEFAULT 'staff'");
    }

    public function down(): void
    {
        // Only safe if no users currently have these roles
        DB::statement("ALTER TABLE users MODIFY COLUMN `role` ENUM(
            'super_admin','owner','doctor','receptionist','nurse','staff','vendor_admin'
        ) NOT NULL DEFAULT 'staff'");
    }
};
