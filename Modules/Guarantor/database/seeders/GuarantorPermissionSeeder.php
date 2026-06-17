<?php

namespace Modules\Guarantor\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GuarantorPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'show guarantors',
            'manage guarantors',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'admin',
            ], [
                'group' => 'guarantors',
            ]);
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'admin')->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }
}
