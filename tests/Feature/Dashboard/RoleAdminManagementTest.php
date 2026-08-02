<?php

use App\Contracts\Admin\RoleRepositoryInterface;
use App\Http\Controllers\Dashboard\AdminController;
use App\Http\Controllers\Dashboard\RoleController;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
});

function createAdminManagementAdmin(array $permissions): Admin
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ], [
            'group' => 'admins',
        ]);
    }

    $admin = Admin::query()->create([
        'name' => 'Admin Manager',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
        'address' => 'Riyadh',
        'job' => 'Manager',
    ]);

    $admin->givePermissionTo($permissions);

    return $admin;
}

it('lists roles with the prams inertia prop', function (): void {
    $admin = createAdminManagementAdmin(['show roles']);
    Role::create(['name' => 'editor', 'guard_name' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get(action([RoleController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Roles/Index')
            ->has('prams')
            ->has('rows')
        );
});

it('includes users_count on each role in the index listing', function (): void {
    $admin = createAdminManagementAdmin(['show roles']);
    $role = Role::create(['name' => 'editor', 'guard_name' => 'admin']);
    $admin->assignRole($role);

    $this->actingAs($admin, 'admin')
        ->get(action([RoleController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Roles/Index')
            ->has('rows.data', fn ($rows) => $rows
                ->where('0.users_count', 1)
                ->etc()
            )
        );
});

test('roles index shows correct users_count for a role with an assigned admin', function (): void {
    // Spatie's Role::users() resolves the related model from guard_name, falling back to
    // auth.defaults.guard on an empty query-builder instance. withCount('users') therefore
    // counts User rows when the ambient guard is "web" — even for admin-guard roles.
    // Keep the ambient guard on web so this regression catches that footgun.
    auth()->shouldUse('web');
    config(['auth.defaults.guard' => 'web']);

    $admin = createAdminManagementAdmin(['show roles']);
    $role = Role::create(['name' => 'content-editor', 'guard_name' => 'admin']);
    $admin->assignRole($role);

    expect($role->users()->count())->toBe(1)
        ->and(Role::query()->withCount('users')->findOrFail($role->id)->users_count)->toBe(0);

    $paginator = app(RoleRepositoryInterface::class)
        ->paginate(new Request);

    $row = $paginator->getCollection()->firstWhere('id', $role->id);

    expect($row)->not->toBeNull()
        ->and($row->users_count)->toBe(1);

    $this->actingAs($admin, 'admin')
        ->get(action([RoleController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Roles/Index')
            ->has('rows.data')
            ->where('rows.data', function ($rows) use ($role): bool {
                $match = collect($rows)->first(
                    fn ($row) => (int) data_get($row, 'id') === (int) $role->id
                );

                return $match !== null && (int) data_get($match, 'users_count') === 1;
            })
        );
});

it('stores a role with synced permissions', function (): void {
    $admin = createAdminManagementAdmin(['create roles']);
    $permission = Permission::firstOrCreate(
        ['name' => 'show roles', 'guard_name' => 'admin'],
        ['group' => 'roles'],
    );

    $this->actingAs($admin, 'admin')
        ->post(action([RoleController::class, 'store']), [
            'name' => 'content-manager',
            'permissions' => [$permission->id],
        ])
        ->assertRedirect(route('dashboard.roles.index'));

    $role = Role::query()->where('name', 'content-manager')->firstOrFail();
    expect($role->permissions->pluck('id')->all())->toContain($permission->id);
});

it('lists admins with the prams inertia prop', function (): void {
    $admin = createAdminManagementAdmin(['show admins']);

    $this->actingAs($admin, 'admin')
        ->get(action([AdminController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Admins/Index')
            ->has('prams')
            ->has('rows')
        );
});

it('stores an admin with roles attached', function (): void {
    Storage::fake('public');

    $admin = createAdminManagementAdmin(['create admins']);
    $role = Role::create(['name' => 'staff', 'guard_name' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->post(action([AdminController::class, 'store']), [
            'name' => 'New Staff',
            'phone' => '0501112233',
            'email' => 'staff@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'address' => 'Jeddah',
            'job' => 'Staff',
            'image' => UploadedFile::fake()->image('avatar.jpg'),
            'roles' => [$role->id],
        ])
        ->assertRedirect(route('dashboard.admins.index'));

    $created = Admin::query()->where('email', 'staff@example.com')->firstOrFail();
    expect($created->roles->pluck('id')->all())->toContain($role->id)
        ->and($created->image)->not->toBeNull();
});

it('updates an admin without requiring a new password', function (): void {
    Storage::fake('public');

    $admin = createAdminManagementAdmin(['edit admins']);
    $role = Role::create(['name' => 'ops', 'guard_name' => 'admin']);
    $target = Admin::query()->create([
        'name' => 'Target Admin',
        'phone' => '0502223344',
        'email' => 'target@example.com',
        'password' => 'password',
        'language' => 'en',
        'address' => 'Dammam',
        'job' => 'Ops',
        'image' => 'admins/old.jpg',
    ]);
    $target->roles()->attach($role->id);

    $this->actingAs($admin, 'admin')
        ->put(action([AdminController::class, 'update'], $target), [
            'name' => 'Updated Admin',
            'phone' => '0502223344',
            'email' => 'target@example.com',
            'address' => 'Dammam',
            'job' => 'Ops Lead',
            'roles' => [$role->id],
        ])
        ->assertRedirect(route('dashboard.admins.index'));

    expect($target->fresh()->name)->toBe('Updated Admin')
        ->and($target->fresh()->job)->toBe('Ops Lead');
});
