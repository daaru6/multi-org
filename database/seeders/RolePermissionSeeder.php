<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view contacts',
            'create contacts',
            'edit contacts',
            'delete contacts',
            'manage organization',
            'invite users',
            'remove users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $memberRole = Role::firstOrCreate(['name' => 'Member']);

        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Member gets limited permissions
        $memberRole->givePermissionTo([
            'view contacts',
            'create contacts',
            'edit contacts',
        ]);
    }
}
