<?php

// database/seeders/PermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Define your permissions
        $permissions = [
            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Leads
            'leads.view',
            'leads.create',
            'leads.update',
            'leads.delete',
        ];

        // 2) Create permissions (idempotent)
        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',   // keep guard consistent
            ]);
        }

        // 3) Create roles (idempotent)
        $admin   = Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $staff   = Role::firstOrCreate(['name' => 'staff',   'guard_name' => 'web']);
        $viewer  = Role::firstOrCreate(['name' => 'viewer',  'guard_name' => 'web']);

        // 4) Assign permissions to roles
        // Admin gets everything
        $admin->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());

        // Manager gets all leads + view users
        $manager->syncPermissions([
            'users.view',
            'leads.view', 'leads.create', 'leads.update', 'leads.delete',
        ]);

        // Staff can create & view leads
        $staff->syncPermissions([
            'leads.view', 'leads.create',
        ]);

        // Viewer can only view leads
        $viewer->syncPermissions([
            'leads.view',
        ]);
    }
}

// php artisan db:seed --class=PermissionSeeder
// php artisan permission:cache-reset