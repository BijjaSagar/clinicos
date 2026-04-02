<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::firstOrCreate(['name' => 'pharmacist', 'guard_name' => 'web']);
        $permissions = [
            'view pharmacy',
            'dispense medicine',
            'manage inventory',
            'view patients',
        ];
        foreach ($permissions as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $role = Role::findByName('pharmacist', 'web');
        $role?->delete();
    }
};
