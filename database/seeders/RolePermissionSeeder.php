<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Single source of truth for admin-guard permissions and Dashboard roles.
 *
 * Edit {@see PERMISSIONS} / {@see ROLES} only — seeding logic below stays stable.
 * Create admin accounts with `php artisan admin:create` (not a seeder).
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Module key => definition.
     *
     * - crud: expand show/create/edit/delete {module}
     * - actions: verb (show|create|edit|delete → "{verb} {module}") or a full permission name
     * - extra: additional full permission names
     * - guard: Spatie guard (default admin)
     *
     * @var array<string, array{guard?: string, crud?: bool, actions?: list<string>, extra?: list<string>}>
     */
    private const array PERMISSIONS = [
        'roles' => ['guard' => 'admin', 'crud' => true],
        'admins' => ['guard' => 'admin', 'crud' => true],
        'providers' => ['guard' => 'admin', 'crud' => true, 'extra' => ['process providers']],
        'users' => ['guard' => 'admin', 'crud' => true],
        'categories' => ['guard' => 'admin', 'crud' => true],
        'propertyCategories' => ['guard' => 'admin', 'crud' => true],
        'propertyTypes' => ['guard' => 'admin', 'crud' => true],
        'carBrands' => ['guard' => 'admin', 'crud' => true],
        'carTypes' => ['guard' => 'admin', 'crud' => true],
        'carCategories' => ['guard' => 'admin', 'crud' => true],
        'propertyAdvisements' => ['guard' => 'admin', 'actions' => ['show', 'edit', 'delete']],
        'carAdvisements' => ['guard' => 'admin', 'actions' => ['show', 'edit', 'delete']],
        'electronicAdvisements' => ['guard' => 'admin', 'actions' => ['show', 'edit', 'delete']],
        'instituteAdvisements' => ['guard' => 'admin', 'actions' => ['show', 'edit', 'delete']],
        'deviceCategories' => ['guard' => 'admin', 'crud' => true],
        'electronicBrands' => ['guard' => 'admin', 'crud' => true],
        'specializations' => ['guard' => 'admin', 'crud' => true],
        'skills' => ['guard' => 'admin', 'crud' => true],
        'regions' => ['guard' => 'admin', 'crud' => true],
        'cities' => ['guard' => 'admin', 'crud' => true],
        'nationalities' => ['guard' => 'admin', 'crud' => true],
        'providerTypes' => ['guard' => 'admin', 'crud' => true],
        'banners' => ['guard' => 'admin', 'crud' => true],
        'pages' => ['guard' => 'admin', 'crud' => true],
        'questions' => ['guard' => 'admin', 'crud' => true],
        'topUpRequests' => ['guard' => 'admin', 'actions' => ['show', 'edit']],
        'messages' => ['guard' => 'admin', 'actions' => ['show', 'delete']],
        'withdrawRequests' => ['guard' => 'admin', 'actions' => ['show', 'edit']],
        'payouts' => ['guard' => 'admin', 'actions' => ['request payouts', 'confirm payouts']],
        'supportTicket' => ['guard' => 'admin', 'actions' => ['show', 'edit']],
        'orders' => ['guard' => 'admin', 'actions' => ['show', 'edit']],
        'opportunities' => ['guard' => 'admin', 'actions' => ['show', 'delete']],
        'guarantors' => ['guard' => 'admin', 'actions' => ['show', 'manage guarantors']],
        'settings' => ['guard' => 'admin', 'actions' => ['show', 'edit']],
        'reviews' => ['guard' => 'admin', 'actions' => ['show', 'delete']],
        'panAnalytics' => ['guard' => 'admin', 'actions' => ['show', 'export panAnalytics', 'delete']],
        'monitoring' => ['guard' => 'admin', 'actions' => ['view monitoring tools']],
    ];

    /**
     * Role => module grants.
     *
     * - '*' = every permission from PERMISSIONS
     * - 'module' = all permissions for that module
     * - 'module' => ['show', 'edit'] = subset (verbs expand the same way as PERMISSIONS actions)
     *
     * @var array<string, '*'|array<int|string, string|list<string>>>
     */
    private const array ROLES = [
        'super-admin' => '*',
        'operations' => [
            'providers',
            'orders',
            'opportunities',
            'guarantors',
        ],
        'finance' => [
            'topUpRequests',
            'withdrawRequests',
            'orders',
            'payouts' => ['request payouts'],
        ],
        'finance-manager' => [
            'topUpRequests',
            'withdrawRequests',
            'orders',
            'payouts' => ['request payouts', 'confirm payouts'],
        ],
        'support' => [
            'supportTicket',
            'messages',
            'questions',
        ],
        'content-manager' => [
            'categories',
            'propertyCategories',
            'propertyTypes',
            'carBrands',
            'carTypes',
            'carCategories',
            'deviceCategories',
            'electronicBrands',
            'specializations',
            'skills',
            'providerTypes',
            'banners',
            'pages',
        ],
        'viewer-monitor' => [
            'monitoring',
        ],
        /*
         * Developer — same effective set as before. Partial modules use action subsets
         * (e.g. finance read-only, no process providers / manage guarantors / role mutations).
         */
        'developer' => [
            'monitoring',
            'settings',
            'panAnalytics',
            'roles' => ['show'],
            'admins' => ['show'],
            'users' => ['show', 'edit'],
            'providers' => ['show', 'edit'],
            'orders',
            'opportunities' => ['show'],
            'guarantors' => ['show'],
            'messages' => ['show'],
            'supportTicket',
            'reviews' => ['show'],
            'topUpRequests' => ['show'],
            'withdrawRequests' => ['show'],
            'propertyAdvisements' => ['show', 'edit'],
            'carAdvisements' => ['show', 'edit'],
            'electronicAdvisements' => ['show', 'edit'],
            'instituteAdvisements' => ['show', 'edit'],
            'categories',
            'propertyCategories',
            'propertyTypes',
            'carBrands',
            'carTypes',
            'carCategories',
            'deviceCategories',
            'electronicBrands',
            'specializations',
            'skills',
            'providerTypes',
            'banners',
            'pages',
            'questions',
            'regions',
            'cities',
            'nationalities',
        ],
    ];

    public function run(): void
    {
        self::validateRoleModuleReferences(self::ROLES, self::PERMISSIONS);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seedPermissions();
        $this->seedRoles();
    }

    /**
     * Fail-fast: every module key in $roles must exist in $permissions.
     *
     * @param  array<string, '*'|array<int|string, string|list<string>>>  $roles
     * @param  array<string, array{guard?: string, crud?: bool, actions?: list<string>, extra?: list<string>}>  $permissions
     */
    public static function validateRoleModuleReferences(array $roles, array $permissions): void
    {
        $validKeys = array_keys($permissions);

        foreach ($roles as $role => $modules) {
            if ($modules === '*') {
                continue;
            }

            if (! is_array($modules)) {
                throw new InvalidArgumentException(
                    "RolePermissionSeeder: role [{$role}] must be '*' or an array of module keys."
                );
            }

            foreach ($modules as $key => $value) {
                $module = is_int($key) ? $value : $key;

                if (! is_string($module)) {
                    throw new InvalidArgumentException(
                        "RolePermissionSeeder: role [{$role}] has an invalid module entry."
                    );
                }

                if (! array_key_exists($module, $permissions)) {
                    $suggestion = self::closestModuleKey($module, $validKeys);
                    $hint = $suggestion !== null ? " Did you mean [{$suggestion}]?" : '';

                    throw new InvalidArgumentException(
                        "RolePermissionSeeder: role [{$role}] references unknown module [{$module}].{$hint}"
                    );
                }

                if (is_int($key)) {
                    continue;
                }

                if (! is_array($value)) {
                    throw new InvalidArgumentException(
                        "RolePermissionSeeder: role [{$role}] module [{$module}] subset must be a list of actions."
                    );
                }

                $allowed = self::modulePermissionNames($module, $permissions[$module]);

                foreach ($value as $action) {
                    $expanded = self::expandAction($module, $action);

                    if (! in_array($expanded, $allowed, true)) {
                        throw new InvalidArgumentException(
                            "RolePermissionSeeder: role [{$role}] requests [{$expanded}] on module [{$module}], which is not defined in PERMISSIONS."
                        );
                    }
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    public static function allPermissionNames(): array
    {
        $names = [];

        foreach (self::PERMISSIONS as $module => $definition) {
            array_push($names, ...self::modulePermissionNames($module, $definition));
        }

        return array_values(array_unique($names));
    }

    /**
     * Resolved role → permission names. `super-admin` => null (all).
     *
     * @return array<string, list<string>|null>
     */
    public static function rolePermissionMap(): array
    {
        $map = [];

        foreach (self::ROLES as $role => $modules) {
            if ($modules === '*') {
                $map[$role] = null;

                continue;
            }

            $names = [];

            foreach ($modules as $key => $value) {
                if (is_int($key)) {
                    array_push($names, ...self::modulePermissionNames($value, self::PERMISSIONS[$value]));

                    continue;
                }

                foreach ($value as $action) {
                    $names[] = self::expandAction($key, $action);
                }
            }

            $map[$role] = array_values(array_unique($names));
        }

        return $map;
    }

    /**
     * @param  array{guard?: string, crud?: bool, actions?: list<string>, extra?: list<string>}  $definition
     * @return list<string>
     */
    public static function modulePermissionNames(string $module, array $definition): array
    {
        $names = [];

        if (($definition['crud'] ?? false) === true) {
            array_push($names, ...self::crudActions($module));
        }

        foreach ($definition['actions'] ?? [] as $action) {
            $names[] = self::expandAction($module, $action);
        }

        foreach ($definition['extra'] ?? [] as $extra) {
            $names[] = $extra;
        }

        return array_values(array_unique($names));
    }

    /**
     * @return list<string>
     */
    public static function crudActions(string $module): array
    {
        return [
            "show {$module}",
            "create {$module}",
            "edit {$module}",
            "delete {$module}",
        ];
    }

    public static function expandAction(string $module, string $action): string
    {
        if (in_array($action, ['show', 'create', 'edit', 'delete'], true)) {
            return "{$action} {$module}";
        }

        return $action;
    }

    /**
     * @param  list<string>  $candidates
     */
    public static function closestModuleKey(string $invalid, array $candidates): ?string
    {
        $best = null;
        $bestDistance = null;

        foreach ($candidates as $candidate) {
            $distance = levenshtein($invalid, $candidate);

            if ($bestDistance === null || $distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }

        if ($bestDistance === null || $bestDistance > max(3, (int) floor(strlen($invalid) / 2))) {
            return null;
        }

        return $best;
    }

    private function seedPermissions(): void
    {
        foreach (self::PERMISSIONS as $module => $definition) {
            $guard = $definition['guard'] ?? 'admin';

            foreach (self::modulePermissionNames($module, $definition) as $name) {
                Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => $guard,
                ], [
                    'group' => $module,
                ]);
            }
        }
    }

    private function seedRoles(): void
    {
        foreach (self::rolePermissionMap() as $roleName => $permissionNames) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'admin',
            ]);

            $names = $permissionNames ?? Permission::query()
                ->where('guard_name', 'admin')
                ->pluck('name')
                ->all();

            $existing = Permission::query()
                ->where('guard_name', 'admin')
                ->whereIn('name', $names)
                ->pluck('name')
                ->all();

            $missing = array_values(array_diff($names, $existing));

            if ($missing !== []) {
                throw new RuntimeException(
                    "RolePermissionSeeder: role [{$roleName}] references unknown permissions: ".implode(', ', $missing)
                );
            }

            $role->syncPermissions($names);
        }
    }
}
