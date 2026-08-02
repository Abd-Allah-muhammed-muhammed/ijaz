<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seedPermissions();
        $this->seedRoles();
    }

    /**
     * @return array<string, list<string>>
     */
    public static function modules(): array
    {
        return [
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
            'electronicAdvisements' => [
                'show electronicAdvisements',
                'edit electronicAdvisements',
                'delete electronicAdvisements',
            ],
            'instituteAdvisements' => [
                'show instituteAdvisements',
                'edit instituteAdvisements',
                'delete instituteAdvisements',
            ],
            'deviceCategories' => [
                'crud',
            ],
            'electronicBrands' => [
                'crud',
            ],
            'specializations' => [
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
            // Canonical names from Modules/*/database/seeders/*PermissionSeeder.
            'opportunities' => [
                'show opportunities',
                'delete opportunities',
            ],
            'guarantors' => [
                'show guarantors',
                'manage guarantors',
            ],
            'settings' => [
                'show settings',
                'edit settings',
            ],
            'reviews' => [
                'show reviews',
                'delete reviews',
            ],
            'panAnalytics' => [
                'show panAnalytics',
                'export panAnalytics',
                'delete panAnalytics',
            ],
            'monitoring' => [
                'view monitoring tools',
            ],
        ];
    }

    /**
     * Role → permission names (admin guard). `super-admin` receives every admin permission.
     *
     * @return array<string, list<string>|null>
     */
    public static function rolePermissionMap(): array
    {
        return [
            'super-admin' => null,
            'operations' => [
                ...self::crud('providers'),
                'process providers',
                'show orders',
                'edit orders',
                'show opportunities',
                'delete opportunities',
                'show guarantors',
                'manage guarantors',
            ],
            'finance' => [
                'show topUpRequests',
                'edit topUpRequests',
                'show withdrawRequests',
                'edit withdrawRequests',
            ],
            'support' => [
                'show supportTicket',
                'edit supportTicket',
                'show messages',
                'delete messages',
                ...self::crud('questions'),
            ],
            'content-manager' => [
                ...self::crud('categories'),
                ...self::crud('propertyCategories'),
                ...self::crud('propertyTypes'),
                ...self::crud('carBrands'),
                ...self::crud('carTypes'),
                ...self::crud('carCategories'),
                ...self::crud('deviceCategories'),
                ...self::crud('electronicBrands'),
                ...self::crud('specializations'),
                ...self::crud('skills'),
                ...self::crud('providerTypes'),
                ...self::crud('banners'),
                ...self::crud('pages'),
            ],
            'viewer-monitor' => [
                'view monitoring tools',
            ],
            /*
             * Developer — build/maintain the project without becoming a full super-admin.
             *
             * Included:
             * - Monitoring (Pulse / Telescope / Log Viewer)
             * - Settings + panAnalytics (technical configuration / diagnostics)
             * - Broad read across ops domains to reproduce bugs
             * - Full CRUD on catalog / geo / CMS surfaces used while shipping UI
             * - Limited write on advisements, orders, users, providers, tickets (exercise flows)
             * - Finance READ only (see wallet UI without approving money movement)
             *
             * Excluded (sensitive / privilege-escalation):
             * - roles & admins mutations (create/edit/delete)
             * - process providers, manage guarantors (business approvals)
             * - edit topUpRequests / edit withdrawRequests (approve payouts/top-ups)
             * - delete users / delete providers / delete reviews
             */
            'developer' => [
                'view monitoring tools',
                'show settings',
                'edit settings',
                'show panAnalytics',
                'export panAnalytics',
                'delete panAnalytics',
                'show roles',
                'show admins',
                'show users',
                'edit users',
                'show providers',
                'edit providers',
                'show orders',
                'edit orders',
                'show opportunities',
                'show guarantors',
                'show messages',
                'show supportTicket',
                'edit supportTicket',
                'show reviews',
                'show topUpRequests',
                'show withdrawRequests',
                'show propertyAdvisements',
                'edit propertyAdvisements',
                'show carAdvisements',
                'edit carAdvisements',
                'show electronicAdvisements',
                'edit electronicAdvisements',
                'show instituteAdvisements',
                'edit instituteAdvisements',
                ...self::crud('categories'),
                ...self::crud('propertyCategories'),
                ...self::crud('propertyTypes'),
                ...self::crud('carBrands'),
                ...self::crud('carTypes'),
                ...self::crud('carCategories'),
                ...self::crud('deviceCategories'),
                ...self::crud('electronicBrands'),
                ...self::crud('specializations'),
                ...self::crud('skills'),
                ...self::crud('providerTypes'),
                ...self::crud('banners'),
                ...self::crud('pages'),
                ...self::crud('questions'),
                ...self::crud('regions'),
                ...self::crud('cities'),
                ...self::crud('nationalities'),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function crud(string $module): array
    {
        return [
            "show $module",
            "create $module",
            "edit $module",
            "delete $module",
        ];
    }

    /**
     * Expand module definitions into concrete permission names (same rules as seeding).
     *
     * @return list<string>
     */
    public static function allPermissionNames(): array
    {
        $names = [];

        foreach (self::modules() as $module => $permissions) {
            foreach ($permissions as $permission) {
                if ($permission === 'crud') {
                    array_push($names, ...self::crud($module));

                    continue;
                }

                $names[] = $permission;
            }
        }

        return array_values(array_unique($names));
    }

    private function seedPermissions(): void
    {
        foreach (self::modules() as $module => $permissions) {
            $crudSeeded = false;

            foreach ($permissions as $permission) {
                if ($permission === 'crud') {
                    if ($crudSeeded) {
                        continue;
                    }

                    $crudSeeded = true;

                    foreach (self::crud($module) as $name) {
                        Permission::firstOrCreate([
                            'name' => $name,
                            'guard_name' => 'admin',
                        ], [
                            'group' => $module,
                        ]);
                    }

                    continue;
                }

                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'admin',
                ], [
                    'group' => $module,
                ]);
            }
        }
    }

    private function seedRoles(): void
    {
        $allAdminPermissions = Permission::query()
            ->where('guard_name', 'admin')
            ->pluck('name')
            ->all();

        foreach (self::rolePermissionMap() as $roleName => $permissionNames) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'admin',
            ]);

            $names = $permissionNames ?? $allAdminPermissions;

            $missing = array_values(array_diff($names, $allAdminPermissions));

            if ($missing !== []) {
                throw new \RuntimeException(
                    "AdminPermissionSeeder: role [{$roleName}] references unknown permissions: ".implode(', ', $missing)
                );
            }

            $role->syncPermissions($names);
        }
    }
}
