<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add pharmacist to the users.role enum column.
 * No-op if migration 000001 already added both roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        $current = DB::select("SHOW COLUMNS FROM users LIKE 'role'");
        if (empty($current)) {
            return;
        }

        $typeStr = $current[0]->Type ?? $current[0]->type ?? '';
        if (str_contains($typeStr, 'pharmacist')) {
            return; // Already added by 000001 migration
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'super_admin','owner','doctor','receptionist','nurse','staff',
            'vendor_admin','lab_technician','pharmacist'
        ) NOT NULL DEFAULT 'staff'");
    }

    public function down(): void
    {
        // No-op: handled by 000001 down()
    }
};
