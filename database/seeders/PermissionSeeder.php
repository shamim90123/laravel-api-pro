<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cache first
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        $permissions = [
            // Users
            'users.view','users.create','users.update','users.delete','users.assign-roles',

            // Leads
            'leads.view','leads.create','leads.update','leads.delete',
            'leads.assign-account-manager','leads.bulk-import','leads.bulk-comment-import','leads.update-status',

            // Lead Contacts
            'lead-contacts.view','lead-contacts.upsert','lead-contacts.delete','lead-contacts.set-primary',

            // Lead Comments
            'lead-comments.view','lead-comments.create','lead-comments.update','lead-comments.delete',

            // Lead Products
            'lead-products.view','lead-products.assign','lead-products.bulk-update',

            // Products
            'products.view','products.create','products.update','products.delete','products.toggle-status',

            // Sale Stages
            'stages.view','stages.create','stages.update','stages.delete','stages.toggle-status',

            // Roles
            'roles.view','roles.create','roles.update','roles.delete',

            // Dashboard
            'dashboard.view',
        ];

        // Create or update permissions
        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
        }

        // Create admin role (if not exists)
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);

        // Assign all permissions to admin role
        $adminRole->syncPermissions(Permission::where('guard_name', $guard)->pluck('name')->toArray());

        // Assign admin role to user with ID 1 (if exists)
        $adminUser = User::find(1);
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
        }

        // Clear cache again
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}


// php artisan db:seed --class=PermissionSeeder
// php artisan permission:cache-reset