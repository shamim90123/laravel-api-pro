<?php
// database/seeders/PermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
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

            // Roles (NEW)
            'roles.view','roles.create','roles.update','roles.delete',

            // Lookups / Dashboard
            'dashboard.view',
        ];


        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => $guard,
            ]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);

        // Give admin everything
        $admin->syncPermissions(Permission::where('guard_name', $guard)->pluck('name')->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

// php artisan db:seed --class=PermissionSeeder
// php artisan permission:cache-reset