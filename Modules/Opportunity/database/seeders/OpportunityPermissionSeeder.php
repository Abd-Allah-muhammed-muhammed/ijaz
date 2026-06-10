<?php

namespace Modules\Opportunity\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class OpportunityPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'show opportunities',
            'delete opportunities',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'admin',
            ], [
                'group' => 'opportunities',
            ]);
        }

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
        $role->givePermissionTo($permissions);
    }
}
