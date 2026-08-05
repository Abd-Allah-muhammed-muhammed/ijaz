<?php

use App\Models\Admin;
use App\Support\LogRedactor;
use App\Support\MonitoringAccess;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Pulse\Recorders\Servers;
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

function assertMonitoringToolAccess(bool $allowed): void
{
    $admin = Auth::guard('admin')->user();

    expect($admin instanceof Admin ? MonitoringAccess::allows($admin) : false)->toBe($allowed);
    expect(Gate::allows('viewPulse'))->toBe($allowed);
    expect(Gate::allows('viewTelescope'))->toBe($allowed);
    expect(Gate::allows('viewLogViewer'))->toBe($allowed);
    expect(Gate::allows('viewHorizon'))->toBe($allowed);

    // Telescope routes are not registered when TELESCOPE_ENABLED=false (phpunit default
    // to keep the suite lightweight). The auth gate/callback still run and are what
    // Authorize middleware consults when the dashboard is enabled.
    expect(Telescope::check(Request::create('/telescope', 'GET')))->toBe($allowed);
    expect(Horizon::check(Request::create('/horizon', 'GET')))->toBe($allowed);
}

it('forbids guests from pulse, telescope, log viewer, and horizon', function (): void {
    $this->get('/pulse')->assertForbidden();
    $this->get('/log-viewer')->assertForbidden();
    $this->get('/horizon')->assertForbidden();
    assertMonitoringToolAccess(false);
});

it('forbids admins without the monitoring permission', function (): void {
    $admin = createMonitoringAdmin();

    $this->actingAs($admin, 'admin')
        ->get('/pulse')
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get('/log-viewer')
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get('/horizon')
        ->assertForbidden();

    $this->actingAs($admin, 'admin');
    assertMonitoringToolAccess(false);
});

it('allows a non-root admin granted only the view monitoring tools permission', function (): void {
    $admin = createMonitoringAdmin(['view monitoring tools']);

    expect($admin->root)->toBeFalse()
        ->and($admin->hasRole('super-admin'))->toBeFalse();

    $this->actingAs($admin, 'admin')
        ->get('/pulse')
        ->assertSuccessful();

    $this->actingAs($admin, 'admin')
        ->get('/log-viewer')
        ->assertSuccessful();

    $this->actingAs($admin, 'admin')
        ->get('/horizon')
        ->assertSuccessful();

    $this->actingAs($admin, 'admin');
    assertMonitoringToolAccess(true);
});

it('allows root admins via gate before even without an explicit permission grant', function (): void {
    $admin = createMonitoringAdmin(root: true);

    $this->actingAs($admin, 'admin')
        ->get('/pulse')
        ->assertSuccessful();

    $this->actingAs($admin, 'admin')
        ->get('/log-viewer')
        ->assertSuccessful();

    $this->actingAs($admin, 'admin')
        ->get('/horizon')
        ->assertSuccessful();

    $this->actingAs($admin, 'admin');
    assertMonitoringToolAccess(true);
});

it('seeds the view monitoring tools permission onto the super-admin role', function (): void {
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::query()->where([
        'name' => 'view monitoring tools',
        'guard_name' => 'admin',
    ])->exists())->toBeTrue();

    $role = Role::findByName('super-admin', 'admin');

    expect($role->hasPermissionTo('view monitoring tools'))->toBeTrue();
});

it('schedules telescope prune daily with a 48-hour retention window', function (): void {
    Artisan::call('schedule:list');

    expect(Artisan::output())
        ->toContain('telescope:prune')
        ->toContain('--hours=48');
});

it('configures pulse storage and ingest retention to trim after 7 days', function (): void {
    expect(config('pulse.storage.trim.keep'))->toBe('7 days')
        ->and(config('pulse.ingest.trim.keep'))->toBe('7 days')
        ->and(config('pulse.ingest.trim.lottery'))->toBe([1, 1_000]);
});

it('stores pulse and telescope on the shared monitoring connection', function (): void {
    expect(config('pulse.storage.database.connection'))->toBe('monitoring')
        ->and(config('telescope.storage.database.connection'))->toBe('monitoring')
        ->and(config('telescope.enabled'))->toBeFalse();
});

it('keeps the redis queue connection wired to the dedicated queue Redis DB', function (): void {
    expect(config('queue.default'))->toBe('sync')
        ->and(config('queue.connections.redis.driver'))->toBe('redis')
        ->and(config('queue.connections.redis.connection'))->toBe('queue')
        ->and(config('database.redis.queue.database'))->toBe('2')
        ->and(config('database.redis.cache.database'))->toBe('1')
        ->and(config('horizon.use'))->toBe('default');
});

it('registers the pulse servers recorder', function (): void {
    expect(config('pulse.recorders'))->toHaveKey(Servers::class);
});

it('redacts sensitive values from log content', function (): void {
    $sample = <<<'LOG'
* **cookie**: XSRF-TOKEN=eyJsecret; ijaz_session=eyJsession
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.abc
password=SuperSecret123
"api_key":"sk-live-abc123"
LOG;

    $redacted = LogRedactor::redact($sample);

    expect($redacted)
        ->toContain('[REDACTED]')
        ->not->toContain('eyJsecret')
        ->not->toContain('eyJsession')
        ->not->toContain('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.abc')
        ->not->toContain('SuperSecret123')
        ->not->toContain('sk-live-abc123');
});
