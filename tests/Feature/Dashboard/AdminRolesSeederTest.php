<?php

use App\Models\Admin;
use Database\Seeders\AdminPermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
    $this->seed(AdminPermissionSeeder::class);
});

it('seeds every declared admin permission name', function (): void {
    $expected = AdminPermissionSeeder::allPermissionNames();

    $actual = Permission::query()
        ->where('guard_name', 'admin')
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    expect($actual)->toEqualCanonicalizing($expected);
});

it('keeps super-admin synced to every admin permission', function (): void {
    $role = Role::findByName('super-admin', 'admin');
    $all = Permission::query()->where('guard_name', 'admin')->pluck('name')->sort()->values();

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toEqual($all->all());
});

it('is idempotent when re-seeded', function (): void {
    $beforeRoles = Role::query()->where('guard_name', 'admin')->count();
    $beforePermissions = Permission::query()->where('guard_name', 'admin')->count();
    $beforeSuperAdminPerms = Role::findByName('super-admin', 'admin')->permissions()->count();

    $this->seed(AdminPermissionSeeder::class);

    expect(Role::query()->where('guard_name', 'admin')->count())->toBe($beforeRoles)
        ->and(Permission::query()->where('guard_name', 'admin')->count())->toBe($beforePermissions)
        ->and(Role::findByName('super-admin', 'admin')->permissions()->count())->toBe($beforeSuperAdminPerms);
});

it('assigns each module role the exact expected permission set', function (string $roleName): void {
    $map = AdminPermissionSeeder::rolePermissionMap();
    $expected = collect($map[$roleName])->sort()->values()->all();

    $role = Role::findByName($roleName, 'admin');
    $actual = $role->permissions->pluck('name')->sort()->values()->all();

    expect($actual)->toEqual($expected)
        ->and($actual)->not->toBeEmpty();
})->with([
    'operations',
    'finance',
    'support',
    'content-manager',
    'viewer-monitor',
    'developer',
]);

it('does not grant developer sensitive finance write or privilege-escalation permissions', function (): void {
    $role = Role::findByName('developer', 'admin');

    expect($role->hasPermissionTo('view monitoring tools'))->toBeTrue()
        ->and($role->hasPermissionTo('show topUpRequests'))->toBeTrue()
        ->and($role->hasPermissionTo('edit topUpRequests'))->toBeFalse()
        ->and($role->hasPermissionTo('edit withdrawRequests'))->toBeFalse()
        ->and($role->hasPermissionTo('manage guarantors'))->toBeFalse()
        ->and($role->hasPermissionTo('process providers'))->toBeFalse()
        ->and($role->hasPermissionTo('create roles'))->toBeFalse()
        ->and($role->hasPermissionTo('edit admins'))->toBeFalse()
        ->and($role->hasPermissionTo('delete users'))->toBeFalse();
});

it('leaves root admin authorization on the Gate::before bypass without requiring super-admin', function (): void {
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
