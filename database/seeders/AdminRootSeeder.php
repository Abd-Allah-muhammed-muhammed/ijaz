<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Modules\Chat\Models\System;
use Spatie\Permission\Models\Role;

class AdminRootSeeder extends Seeder
{
    /**
     * Create the root admin account (Gate::before bypass via root=true).
     *
     * Also assigns the super-admin role when it exists (belt-and-suspenders with the
     * Gate bypass — UI/role listings stay consistent). Roles are seeded first by
     * {@see RolePermissionSeeder}.
     */
    public function run(): void
    {
        System::firstOrCreate(['id' => 1]);

        $password = 'kolB20Euzx';

        $root = Admin::firstOrCreate(['root' => true], [
            'name' => 'Root',
            'password' => $password,
            'address' => 'Root Address',
            'email_verified_at' => now(),
            'email' => 'root@nagaz.com',
            'phone' => '96600000000',
            'job' => 'Root Job',
        ]);

        if (Role::query()->where(['name' => 'super-admin', 'guard_name' => 'admin'])->exists()) {
            $root->assignRole('super-admin');
        }

        if ($this->command) {
            $this->command->info("RootPassword = {$password}");
        }
    }
}
