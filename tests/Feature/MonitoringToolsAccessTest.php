<?php

use App\Models\Admin;
use Database\Seeders\AdminPermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\Telescope;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function createMonitoringAdmin(array $permissions = [], bool $root = false): Admin
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ], [
            'group' => 'monitoring',
        ]);
    }

    $admin = Admin::query()->create([
        'name' => 'Monitoring Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
        'address' => 'Riyadh',
        'job' => 'Ops',
    ]);

    if ($root) {
        $admin->forceFill(['root' => true])->save();
    }

    if ($permissions !== []) {
        $admin->givePermissionTo($permissions);
    }

    return $admin->fresh();
}

function assertTelescopeAccess(bool $allowed): void
{
    // Telescope routes are not registered when TELESCOPE_ENABLED=false (phpunit default
    // to keep the suite lightweight). The auth gate/callback still run and are what
    // Authorize middleware consults when the dashboard is enabled.
    expect(Gate::allows('viewTelescope'))->toBe($allowed);
    expect(Telescope::check(Request::create('/telescope', 'GET')))->toBe($allowed);
}

it('forbids guests from pulse and telescope', function (): void {
    $this->get('/pulse')->assertForbidden();
    assertTelescopeAccess(false);
});

it('forbids admins without the monitoring permission', function (): void {
    $admin = createMonitoringAdmin();

    $this->actingAs($admin, 'admin')
        ->get('/pulse')
        ->assertForbidden();

    $this->actingAs($admin, 'admin');
    assertTelescopeAccess(false);
});

it('allows admins with the view monitoring tools permission', function (): void {
    $admin = createMonitoringAdmin(['view monitoring tools']);

    $this->actingAs($admin, 'admin')
        ->get('/pulse')
        ->assertSuccessful();

    $this->actingAs($admin, 'admin');
    assertTelescopeAccess(true);
});

it('allows root admins via gate before even without the permission', function (): void {
    $admin = createMonitoringAdmin(root: true);

    $this->actingAs($admin, 'admin')
        ->get('/pulse')
        ->assertSuccessful();

    $this->actingAs($admin, 'admin');
    assertTelescopeAccess(true);
});

it('seeds the view monitoring tools permission onto the super-admin role', function (): void {
    $this->seed(AdminPermissionSeeder::class);

    expect(Permission::query()->where([
        'name' => 'view monitoring tools',
        'guard_name' => 'admin',
    ])->exists())->toBeTrue();

    $role = Role::findByName('super-admin', 'admin');

    expect($role->hasPermissionTo('view monitoring tools'))->toBeTrue();
});

it('schedules telescope prune daily', function (): void {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toContain('telescope:prune');
});

it('stores pulse and telescope on the shared monitoring connection', function (): void {
    expect(config('pulse.storage.database.connection'))->toBe('monitoring')
        ->and(config('telescope.storage.database.connection'))->toBe('monitoring')
        ->and(config('telescope.enabled'))->toBeFalse();
});
