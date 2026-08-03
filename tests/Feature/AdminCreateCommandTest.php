<?php

use App\Models\Admin;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

test('admin:create creates a root admin when confirmed', function (): void {
    $this->artisan('admin:create')
        ->expectsQuestion('Name', 'Root Admin')
        ->expectsQuestion('Email', 'root@example.com')
        ->expectsQuestion('Phone', '966500000001')
        ->expectsQuestion('Password', 'SecurePass123!')
        ->expectsQuestion('Confirm password', 'SecurePass123!')
        ->expectsConfirmation('Is this a root account?', 'yes')
        ->expectsOutputToContain('Admin [Root Admin] <root@example.com> created with role: root (super-admin).')
        ->expectsOutputToContain('Password was set interactively')
        ->doesntExpectOutputToContain('SecurePass123!')
        ->assertSuccessful();

    $admin = Admin::query()->where('email', 'root@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->name)->toBe('Root Admin')
        ->and($admin->phone)->toBe('966500000001')
        ->and((bool) $admin->root)->toBeTrue()
        ->and($admin->hasRole('super-admin'))->toBeTrue()
        ->and(Hash::check('SecurePass123!', $admin->password))->toBeTrue();
});

test('admin:create creates a non-root admin with the selected role when not root', function (): void {
    $roleNames = Role::query()->where('guard_name', 'admin')->orderBy('name')->pluck('name')->all();

    $this->artisan('admin:create')
        ->expectsQuestion('Name', 'Ops Admin')
        ->expectsQuestion('Email', 'ops@example.com')
        ->expectsQuestion('Phone', '966500000002')
        ->expectsQuestion('Password', 'SecurePass123!')
        ->expectsQuestion('Confirm password', 'SecurePass123!')
        ->expectsConfirmation('Is this a root account?', 'no')
        ->expectsChoice('Select a role', 'operations', $roleNames)
        ->expectsOutputToContain('Admin [Ops Admin] <ops@example.com> created with role: operations.')
        ->doesntExpectOutputToContain('SecurePass123!')
        ->assertSuccessful();

    $admin = Admin::query()->where('email', 'ops@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and((bool) $admin->root)->toBeFalse()
        ->and($admin->hasRole('operations'))->toBeTrue()
        ->and($admin->hasRole('super-admin'))->toBeFalse()
        ->and(Hash::check('SecurePass123!', $admin->password))->toBeTrue();
});

test('admin:create rejects a duplicate email', function (): void {
    Admin::query()->create([
        'name' => 'Existing',
        'email' => 'taken@example.com',
        'phone' => '966500000003',
        'password' => 'password',
        'language' => 'en',
    ]);

    $this->artisan('admin:create')
        ->expectsQuestion('Name', 'Another Admin')
        ->expectsQuestion('Email', 'taken@example.com')
        ->expectsQuestion('Phone', '966500000004')
        ->expectsQuestion('Password', 'SecurePass123!')
        ->expectsQuestion('Confirm password', 'SecurePass123!')
        ->expectsOutputToContain('An admin with this email already exists.')
        ->assertFailed();

    expect(Admin::query()->where('email', 'taken@example.com')->count())->toBe(1);
});

test('admin:create requires password confirmation to match', function (): void {
    $this->artisan('admin:create')
        ->expectsQuestion('Name', 'Mismatch Admin')
        ->expectsQuestion('Email', 'mismatch@example.com')
        ->expectsQuestion('Phone', '966500000005')
        ->expectsQuestion('Password', 'SecurePass123!')
        ->expectsQuestion('Confirm password', 'DifferentPass123!')
        ->expectsOutputToContain('Password confirmation does not match.')
        ->assertFailed();

    expect(Admin::query()->where('email', 'mismatch@example.com')->exists())->toBeFalse();
});

test('admin:create fails clearly when no roles exist for a non-root admin', function (): void {
    Role::query()->where('guard_name', 'admin')->delete();
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    expect(Role::query()->where('guard_name', 'admin')->count())->toBe(0);

    $this->artisan('admin:create')
        ->expectsQuestion('Name', 'No Roles Admin')
        ->expectsQuestion('Email', 'noroles@example.com')
        ->expectsQuestion('Phone', '966500000006')
        ->expectsQuestion('Password', 'SecurePass123!')
        ->expectsQuestion('Confirm password', 'SecurePass123!')
        ->expectsConfirmation('Is this a root account?', 'no')
        ->expectsOutputToContain('No admin-guard roles found')
        ->expectsOutputToContain('RolePermissionSeeder')
        ->assertFailed();

    expect(Admin::query()->where('email', 'noroles@example.com')->exists())->toBeFalse();
});
