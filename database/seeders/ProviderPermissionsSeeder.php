<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ProviderPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::firstOrCreate([
            'name' => 'view_providers',
            'guard_name' => 'provider',
            'group' => 'provider',
        ]);
        Permission::firstOrCreate([
            'name' => 'create_providers',
            'guard_name' => 'provider',
            'group' => 'provider',
        ]);
        Permission::firstOrCreate([
            'name' => 'edit_providers',
            'guard_name' => 'provider',
            'group' => 'provider',

        ]);
        Permission::firstOrCreate([
            'name' => 'delete_providers',
            'guard_name' => 'provider',
            'group' => 'provider',
        ]);
        Permission::firstOrCreate([
            'name' => 'edit_providers',
            'guard_name' => 'provider',
            'group' => 'provider',
        ]);

        Permission::firstOrCreate([
            'name' => 'view_services',
            'guard_name' => 'provider',
            'group' => 'services',
        ]);
        Permission::firstOrCreate([
            'name' => 'create_services',
            'guard_name' => 'provider',
            'group' => 'services',
        ]);
        Permission::firstOrCreate([
            'name' => 'edit_services',
            'guard_name' => 'provider',
            'group' => 'services',
        ]);
        Permission::firstOrCreate([
            'name' => 'delete_services',
            'guard_name' => 'provider',
            'group' => 'services',
        ]);

        Permission::firstOrCreate([
            'name' => 'view_orders',
            'guard_name' => 'provider',
            'group' => 'orders',
        ]);
        Permission::firstOrCreate([
            'name' => 'create_orders',
            'guard_name' => 'provider',
            'group' => 'orders',
        ]);
        Permission::firstOrCreate([
            'name' => 'edit_orders',
            'guard_name' => 'provider',
        ]);
        Permission::firstOrCreate([
            'name' => 'delete_orders',
            'guard_name' => 'provider',
            'group' => 'orders',
        ]);

        Permission::firstOrCreate([
            'name' => 'view_jobs',
            'guard_name' => 'provider',
            'group' => 'jobs',
        ]);
        Permission::firstOrCreate([
            'name' => 'accept_jobs',
            'guard_name' => 'provider',
            'group' => 'jobs',
        ]);
    }
}
