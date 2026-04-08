<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $modules = [
            'roles' => [
                'crud',
            ],
            'admins' => [
                'crud',
            ],
            'providers' => [
                'crud',
                'process providers',
            ],
            'users' => [
                'crud',
            ],
            'categories' => [
                'crud',
            ],
            'propertyCategories' => [
                'crud',
            ],
            'propertyTypes' => [
                'crud',
            ],
            'carBrands' => [
                'crud',
            ],
            'carTypes' => [
                'crud',
            ],
            'carCategories' => [
                'crud',
            ],
            'propertyAdvisements' => [
                'show propertyAdvisements',
                'edit propertyAdvisements',
                'delete propertyAdvisements',
            ],
            'carAdvisements' => [
                'show carAdvisements',
                'edit carAdvisements',
                'delete carAdvisements',
            ],
            'deviceCategories' => [
                'crud',
            ],
            'skills' => [
                'crud',
            ],
            'regions' => [
                'crud',
            ],
            'cities' => [
                'crud',
            ],
            'nationalities' => [
                'crud',
            ],
            'providerTypes' => [
                'crud',
            ],
            'banners' => [
                'crud',
            ],
            'pages' => [
                'crud',
            ],
            'questions' => [
                'crud',
            ],
            'topUpRequests' => [
                'show topUpRequests',
                'edit topUpRequests',
            ],
            'messages' => [
                'show messages',
                'delete messages',
            ],
            'withdrawRequests' => [
                'show withdrawRequests',
                'edit withdrawRequests',
            ],
            'supportTicket' => [
                'show supportTicket',
                'edit supportTicket',
            ],
            'orders' => [
                'show orders',
                'edit orders',
            ],
            'settings' => [
                'show settings',
                'edit settings',
            ],
            'panAnalytics' => [
                'show panAnalytics',
                'export panAnalytics',
                'delete panAnalytics',
            ],
        ];

        foreach ($modules as $module => $permissions) {
            $curd = false;
            foreach ($permissions as $permission) {
                if ($permission === 'crud') {
                    if ($curd) {
                        continue;
                    }
                    $curd = true;
                    Permission::firstOrCreate([
                        'name' => "show $module",
                        'guard_name' => 'admin',
                    ], [
                        'group' => $module,
                    ]);
                    Permission::firstOrCreate([
                        'name' => "create $module",
                        'guard_name' => 'admin',
                    ], [
                        'group' => $module,
                    ]);
                    Permission::firstOrCreate([
                        'name' => "edit $module",
                        'guard_name' => 'admin',
                    ], [
                        'group' => $module,
                    ]);
                    Permission::firstOrCreate([
                        'name' => "delete $module",
                        'guard_name' => 'admin',
                    ], [
                        'group' => $module,
                    ]);
                } else {
                    Permission::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => 'admin',
                    ], [
                        'group' => $module,
                    ]);
                }
            }
        }

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
        $role->syncPermissions(Permission::where('guard_name', 'admin')->pluck('name')->all());

    }
}
