<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Create lab_technician role if not exists
        $role = Role::firstOrCreate(['name' => 'lab_technician', 'guard_name' => 'web']);

        // Permissions for lab technician
        $permissions = [
            'view lab orders',
            'update lab samples',
            'enter lab results',
            'view patients',
            'view lab catalog',
        ];

        foreach ($permissions as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $role = Role::findByName('lab_technician', 'web');
        $role?->delete();
    }
};
