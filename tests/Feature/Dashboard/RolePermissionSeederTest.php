<?php

use App\Models\Admin;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
    $this->seed(RolePermissionSeeder::class);
});

/**
 * Exact counts from the pre-refactor audit — must not drift.
 *
 * @return array<string, int>
 */
function expectedRolePermissionCounts(): array
{
    return [
        'super-admin' => 121,
        'operations' => 11,
        'finance' => 4,
        'support' => 8,
        'content-manager' => 52,
        'viewer-monitor' => 1,
        'developer' => 98,
    ];
}

it('seeds every declared admin permission name', function (): void {
    $expected = RolePermissionSeeder::allPermissionNames();

    $actual = Permission::query()
        ->where('guard_name', 'admin')
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    expect($actual)->toEqualCanonicalizing($expected)
        ->and($actual)->toHaveCount(121);
});

it('keeps super-admin synced to every admin permission', function (): void {
    $role = Role::findByName('super-admin', 'admin');
    $all = Permission::query()->where('guard_name', 'admin')->pluck('name')->sort()->values();

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toEqual($all->all())
        ->and($role->permissions)->toHaveCount(121);
});

it('is idempotent when re-seeded', function (): void {
    $beforeRoles = Role::query()->where('guard_name', 'admin')->count();
    $beforePermissions = Permission::query()->where('guard_name', 'admin')->count();
    $beforeSuperAdminPerms = Role::findByName('super-admin', 'admin')->permissions()->count();

    $this->seed(RolePermissionSeeder::class);

    expect(Role::query()->where('guard_name', 'admin')->count())->toBe($beforeRoles)
        ->and(Permission::query()->where('guard_name', 'admin')->count())->toBe($beforePermissions)
        ->and(Role::findByName('super-admin', 'admin')->permissions()->count())->toBe($beforeSuperAdminPerms);
});

it('assigns each module role the exact expected permission set and count', function (string $roleName): void {
    $map = RolePermissionSeeder::rolePermissionMap();
    $expected = collect($map[$roleName])->sort()->values()->all();
    $expectedCount = expectedRolePermissionCounts()[$roleName];

    $role = Role::findByName($roleName, 'admin');
    $actual = $role->permissions->pluck('name')->sort()->values()->all();

    expect($actual)->toEqual($expected)
        ->and($actual)->toHaveCount($expectedCount);
})->with([
    'operations',
    'finance',
    'support',
    'content-manager',
    'viewer-monitor',
    'developer',
]);

it('preserves the audited permission counts for every seeded role', function (): void {
    foreach (expectedRolePermissionCounts() as $roleName => $count) {
        expect(Role::findByName($roleName, 'admin')->permissions()->count())
            ->toBe($count, "Role [{$roleName}] permission count drifted from audit.");
    }
});

it('does not grant developer sensitive finance write or privilege-escalation permissions', function (): void {
    $role = Role::findByName('developer', 'admin');

    expect($role->hasPermissionTo('view monitoring tools'))->toBeTrue()
        ->and($role->hasPermissionTo('show topUpRequests'))->toBeTrue()
        ->and($role->hasPermissionTo('edit topUpRequests'))->toBeFalse()
        ->and($role->hasPermissionTo('edit withdrawRequests'))->toBeFalse()
        ->and($role->hasPermissionTo('request payouts'))->toBeFalse()
        ->and($role->hasPermissionTo('confirm payouts'))->toBeFalse()
        ->and($role->hasPermissionTo('manage guarantors'))->toBeFalse()
        ->and($role->hasPermissionTo('process providers'))->toBeFalse()
        ->and($role->hasPermissionTo('create roles'))->toBeFalse()
        ->and($role->hasPermissionTo('edit admins'))->toBeFalse()
        ->and($role->hasPermissionTo('delete users'))->toBeFalse();
});

it('allows root via Gate::before even without any role assignment', function (): void {
    $root = Admin::query()->create([
        'name' => 'Root Probe',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
        'address' => 'Riyadh',
        'job' => 'Root',
    ]);
    $root->forceFill(['root' => true])->save();

    expect($root->fresh()->hasRole('super-admin'))->toBeFalse();

    $this->actingAs($root->fresh(), 'admin');

    expect(Gate::allows('view monitoring tools'))->toBeTrue()
        ->and(Gate::allows('edit withdrawRequests'))->toBeTrue()
        ->and(Gate::allows('create roles'))->toBeTrue();
});

it('fails fast with a clear typo suggestion for an invalid role module key', function (): void {
    $permissions = [
        'categories' => ['guard' => 'admin', 'crud' => true],
        'providers' => ['guard' => 'admin', 'crud' => true],
    ];

    expect(fn () => RolePermissionSeeder::validateRoleModuleReferences([
        'operations' => ['categories', 'providerz'],
    ], $permissions))
        ->toThrow(
            InvalidArgumentException::class,
            'RolePermissionSeeder: role [operations] references unknown module [providerz]. Did you mean [providers]?'
        );
});
