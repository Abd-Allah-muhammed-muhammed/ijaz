<?php

use Modules\Settings\Actions\Setting\ListPublicSettingsAction;
use Modules\Settings\Http\Controllers\Dashboard\SettingController;
use Modules\Settings\Models\Setting;

test('admin with show settings can view settings dashboard index', function () {
    withoutSettingsDashboardLocaleMiddleware();
    $admin = createSettingsDashboardAdmin(['show settings']);

    Setting::query()->updateOrCreate(
        ['key' => 'phone'],
        ['content' => '966500000000', 'group' => 'general'],
    );

    $this->actingAs($admin, 'admin')
        ->get(action([SettingController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Settings/Index')
            ->has('groups')
            ->has('groupOrder')
        );
});

test('admin with edit settings can update settings and invalidate cache', function () {
    withoutSettingsDashboardLocaleMiddleware();
    $admin = createSettingsDashboardAdmin(['edit settings']);

    Setting::query()->updateOrCreate(
        ['key' => 'phone'],
        ['content' => '966500000000', 'group' => 'general', 'is_public' => true],
    );

    cache()->forever('settings', collect(['phone' => '966500000000']));
    app()->instance('settings', collect(['phone' => '966500000000']));
    app(ListPublicSettingsAction::class)->handle();

    $this->actingAs($admin, 'admin')
        ->put(action([SettingController::class, 'update']), [
            'group' => 'general',
            'values' => [
                'phone' => '966511111111',
            ],
            'is_public' => [
                'phone' => true,
            ],
        ])
        ->assertRedirect();

    expect(Setting::query()->where('key', 'phone')->value('content'))->toBe('966511111111')
        ->and((bool) Setting::query()->where('key', 'phone')->value('is_public'))->toBeTrue()
        ->and(cache()->get('settings'))->toBeNull()
        ->and(app('settings')->get('phone'))->toBe('966511111111')
        ->and(app(ListPublicSettingsAction::class)->handle()['phone'])
        ->toBe('966511111111');
});

test('admin without show settings cannot access settings dashboard', function () {
    withoutSettingsDashboardLocaleMiddleware();
    $admin = createSettingsDashboardAdmin(['show users']);

    $this->actingAs($admin, 'admin')
        ->get(action([SettingController::class, 'index']))
        ->assertForbidden();
});
