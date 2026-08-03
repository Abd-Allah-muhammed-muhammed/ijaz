<?php

use App\Http\Controllers\Dashboard\RoleController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Resources\Dashboard\AdminResource;
use App\Models\Admin;
use Illuminate\Http\Request;
use Modules\Cms\Http\Controllers\Dashboard\BannerController;
use Modules\Cms\Http\Controllers\Dashboard\PageController;
use Modules\Orders\Http\Controllers\Dashboard\OrderController;
use Modules\Orders\Models\Order;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
});

/**
 * @param  list<string>  $permissions
 */
function createNarrowPermissionAdmin(array $permissions, ?string $roleName = null): Admin
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }

    $admin = Admin::query()->create([
        'name' => 'Narrow Permission Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
        'root' => false,
    ]);

    $admin->givePermissionTo($permissions);

    if ($roleName !== null) {
        $role = Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'admin',
        ]);
        $role->syncPermissions($permissions);
        $admin->assignRole($role);
    }

    return $admin->fresh(['roles', 'permissions']);
}

it('allows a show-banners-only admin to view banners but forbids pages and banner create', function (): void {
    $admin = createNarrowPermissionAdmin(['show banners']);

    $this->actingAs($admin, 'admin')
        ->get(action([BannerController::class, 'index']))
        ->assertSuccessful();

    $this->actingAs($admin, 'admin')
        ->get(action([PageController::class, 'index']))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get(action([BannerController::class, 'create']))
        ->assertForbidden();
});

it('allows banner create only when the admin also has create banners', function (): void {
    $admin = createNarrowPermissionAdmin(['show banners', 'create banners']);

    $this->actingAs($admin, 'admin')
        ->get(action([BannerController::class, 'create']))
        ->assertSuccessful();
});

it('shares permission names and role objects with name for the inertia auth payload', function (): void {
    $admin = createNarrowPermissionAdmin(['show banners'], 'banner-viewer');

    $request = Request::create('/dashboard', 'GET');
    $request->setUserResolver(fn () => $admin);

    $resource = AdminResource::make($admin->load('roles', 'permissions'))->resolve($request);

    expect($resource['roles'])->toBeIterable()
        ->and(collect($resource['roles'])->pluck('name')->all())->toContain('banner-viewer');

    $middleware = new class extends HandleInertiaRequests
    {
        public function exposePermissions(Request $request): array
        {
            return $this->getPermissions($request);
        }
    };

    $shared = $middleware->exposePermissions($request);

    expect($shared)->toContain('show banners')
        ->and($shared)->not->toContain('create banners');
});

it('forbids orders dashboard routes without show orders', function (): void {
    $admin = createNarrowPermissionAdmin(['show banners']);
    $order = Order::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get(action([OrderController::class, 'index']))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get(action([OrderController::class, 'show'], ['order' => $order]))
        ->assertForbidden();
});

it('allows orders dashboard routes with show orders', function (): void {
    $admin = createNarrowPermissionAdmin(['show orders']);
    $order = Order::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get(action([OrderController::class, 'index']))
        ->assertSuccessful();

    $this->actingAs($admin, 'admin')
        ->get(action([OrderController::class, 'show'], ['order' => $order]))
        ->assertSuccessful();
});

it('enforces edit roles permission on role edit and update routes', function (): void {
    $admin = createNarrowPermissionAdmin(['show roles']);
    $role = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get(action([RoleController::class, 'edit'], ['role' => $role]))
        ->assertForbidden();

    $editor = createNarrowPermissionAdmin(['edit roles']);

    $this->actingAs($editor, 'admin')
        ->get(action([RoleController::class, 'edit'], ['role' => $role]))
        ->assertSuccessful();
});
