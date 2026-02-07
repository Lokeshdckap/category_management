<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            'reorder categories',
            'change category status',
            'view products',
            'create products',
            'edit products',
            'delete products',

        ];

        foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'sanctum',
                ]);
            }

            $admin = Role::firstOrCreate([
                'name' => 'admin',
                'guard_name' => 'sanctum',
            ]);
            
             $customer = Role::firstOrCreate([
                'name' => 'customer',
                'guard_name' => 'sanctum',
            ]);
            
        $admin->syncPermissions($permissions);
    }
}
