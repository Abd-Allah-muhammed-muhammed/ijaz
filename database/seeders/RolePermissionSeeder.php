<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the admin-guard permission catalog and composes Dashboard roles from
 * reusable logical groups (Catalog, Geo, CMS, Finance, Ops, …) — no duplicated lists.
 *
 * Account creation (root admin) stays in {@see AdminRootSeeder}.
 */
class RolePermissionSeeder extends Seeder
{
    private const string GUARD = 'admin';

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seedPermissions();
        $this->seedRoles();
    }

    /**
     * Spatie permission registry: DB `group` column => permission definitions.
     * Use `'crud'` to expand into show/create/edit/delete for that module key.
     *
     * @return array<string, list<string>>
     */
    public static function permissionRegistry(): array
    {
        return [
            'roles' => ['crud'],
            'admins' => ['crud'],
            'providers' => ['crud', 'process providers'],
            'users' => ['crud'],
            'categories' => ['crud'],
            'propertyCategories' => ['crud'],
            'propertyTypes' => ['crud'],
            'carBrands' => ['crud'],
            'carTypes' => ['crud'],
            'carCategories' => ['crud'],
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
            'deviceCategories' => ['crud'],
            'electronicBrands' => ['crud'],
            'specializations' => ['crud'],
            'skills' => ['crud'],
            'regions' => ['crud'],
            'cities' => ['crud'],
            'nationalities' => ['crud'],
            'providerTypes' => ['crud'],
            'banners' => ['crud'],
            'pages' => ['crud'],
            'questions' => ['crud'],
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
     * Reusable permission groups — defined once, composed into roles.
     *
     * @return array<string, list<string>>
     */
    public static function groups(): array
    {
        return [
            'catalog' => [
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
            ],
            'geo' => [
                ...self::crud('regions'),
                ...self::crud('cities'),
                ...self::crud('nationalities'),
            ],
            'cms' => [
                ...self::crud('banners'),
                ...self::crud('pages'),
            ],
            'faq' => self::crud('questions'),
            'finance' => [
                'show topUpRequests',
                'edit topUpRequests',
                'show withdrawRequests',
                'edit withdrawRequests',
            ],
            'finance_read' => [
                'show topUpRequests',
                'show withdrawRequests',
            ],
            'providers' => [
                ...self::crud('providers'),
                'process providers',
            ],
            'providers_edit' => [
                'show providers',
                'edit providers',
            ],
            'orders' => [
                'show orders',
                'edit orders',
            ],
            'opportunities' => [
                'show opportunities',
                'delete opportunities',
            ],
            'opportunities_read' => [
                'show opportunities',
            ],
            'guarantors' => [
                'show guarantors',
                'manage guarantors',
            ],
            'guarantors_read' => [
                'show guarantors',
            ],
            'support_inbox' => [
                'show supportTicket',
                'edit supportTicket',
                'show messages',
                'delete messages',
            ],
            'support_inbox_read' => [
                'show supportTicket',
                'edit supportTicket',
                'show messages',
            ],
            'classifieds_write' => [
                'show propertyAdvisements',
                'edit propertyAdvisements',
                'show carAdvisements',
                'edit carAdvisements',
                'show electronicAdvisements',
                'edit electronicAdvisements',
                'show instituteAdvisements',
                'edit instituteAdvisements',
            ],
            'monitoring' => [
                'view monitoring tools',
            ],
            'settings' => [
                'show settings',
                'edit settings',
            ],
            'analytics' => [
                'show panAnalytics',
                'export panAnalytics',
                'delete panAnalytics',
            ],
            'users_edit' => [
                'show users',
                'edit users',
            ],
            'reviews_read' => [
                'show reviews',
            ],
            'access_inspect' => [
                'show roles',
                'show admins',
            ],
        ];
    }

    /**
     * Role name => list of group keys to merge, or null for every admin permission.
     * Extra permission names may be appended via {@see rolePermissionMap()}.
     *
     * @return array<string, list<string>|null>
     */
    public static function roleGroups(): array
    {
        return [
            'super-admin' => null,
            'operations' => [
                'providers',
                'orders',
                'opportunities',
                'guarantors',
            ],
            'finance' => [
                'finance',
            ],
            'support' => [
                'support_inbox',
                'faq',
            ],
            'content-manager' => [
                'catalog',
                'cms',
            ],
            'viewer-monitor' => [
                'monitoring',
            ],
            /*
             * Developer — build/maintain without full super-admin.
             * See group list; excludes role/admin mutations, process providers,
             * manage guarantors, finance writes, and destructive user/provider deletes.
             */
            'developer' => [
                'monitoring',
                'settings',
                'analytics',
                'access_inspect',
                'users_edit',
                'providers_edit',
                'orders',
                'opportunities_read',
                'guarantors_read',
                'support_inbox_read',
                'reviews_read',
                'finance_read',
                'classifieds_write',
                'catalog',
                'cms',
                'faq',
                'geo',
            ],
        ];
    }

    /**
     * Resolved role → permission names (admin guard). `super-admin` => null (all).
     *
     * @return array<string, list<string>|null>
     */
    public static function rolePermissionMap(): array
    {
        $groups = self::groups();
        $map = [];

        foreach (self::roleGroups() as $role => $groupKeys) {
            if ($groupKeys === null) {
                $map[$role] = null;

                continue;
            }

            $names = [];

            foreach ($groupKeys as $groupKey) {
                if (! isset($groups[$groupKey])) {
                    throw new \InvalidArgumentException("Unknown permission group [{$groupKey}] for role [{$role}].");
                }

                array_push($names, ...$groups[$groupKey]);
            }

            $map[$role] = array_values(array_unique($names));
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    public static function crud(string $module): array
    {
        return [
            "show {$module}",
            "create {$module}",
            "edit {$module}",
            "delete {$module}",
        ];
    }

    /**
     * @return list<string>
     */
    public static function allPermissionNames(): array
    {
        $names = [];

        foreach (self::permissionRegistry() as $module => $permissions) {
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
        foreach (self::permissionRegistry() as $module => $permissions) {
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
                            'guard_name' => self::GUARD,
                        ], [
                            'group' => $module,
                        ]);
                    }

                    continue;
                }

                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => self::GUARD,
                ], [
                    'group' => $module,
                ]);
            }
        }
    }

    private function seedRoles(): void
    {
        $allAdminPermissions = Permission::query()
            ->where('guard_name', self::GUARD)
            ->pluck('name')
            ->all();

        foreach (self::rolePermissionMap() as $roleName => $permissionNames) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => self::GUARD,
            ]);

            $names = $permissionNames ?? $allAdminPermissions;

            $missing = array_values(array_diff($names, $allAdminPermissions));

            if ($missing !== []) {
                throw new \RuntimeException(
                    "RolePermissionSeeder: role [{$roleName}] references unknown permissions: ".implode(', ', $missing)
                );
            }

            $role->syncPermissions($names);
        }
    }
}
