<?php

use App\Support\LookupCache;
use Modules\Settings\Http\Controllers\Api\V1\SettingController as ApiSettingController;
use Modules\Settings\Http\Controllers\Dashboard\SettingController as DashboardSettingController;
use Modules\Settings\Models\Setting;

beforeEach(function () {
    cache()->forget('settings');
    app()->forgetInstance('settings');
    LookupCache::forget('settings:public');
});

test('public settings API only returns settings marked is_public', function () {
    Setting::query()->updateOrCreate(
        ['key' => 'phone'],
        ['content' => '966500000000', 'group' => 'general', 'is_public' => true],
    );
    Setting::query()->updateOrCreate(
        ['key' => 'secret_fee'],
        ['content' => '99', 'group' => 'payment', 'is_public' => false],
    );

    $this->getJson(action([ApiSettingController::class, 'settings']))
        ->assertSuccessful()
        ->assertJsonPath('data.phone', '966500000000')
        ->assertJsonMissing(['secret_fee' => '99']);
});

test('newly created setting defaults to private', function () {
    $setting = Setting::query()->create([
        'key' => 'brand_new_private_key',
        'content' => 'hidden',
        'group' => 'general',
    ]);

    expect($setting->fresh()->is_public)->toBeFalse();

    $this->getJson(action([ApiSettingController::class, 'settings']))
        ->assertSuccessful()
        ->assertJsonMissing(['brand_new_private_key' => 'hidden']);
});

test('dashboard update ignores is_public toggles — API exposure stays seeder/DB controlled', function () {
    withoutSettingsDashboardLocaleMiddleware();
    $admin = createSettingsDashboardAdmin(['edit settings']);

    Setting::query()->updateOrCreate(
        ['key' => 'phone'],
        ['content' => '966500000000', 'group' => 'general', 'is_public' => true],
    );

    $this->getJson(action([ApiSettingController::class, 'settings']))
        ->assertSuccessful()
        ->assertJsonPath('data.phone', '966500000000');

    $this->actingAs($admin, 'admin')
        ->put(action([DashboardSettingController::class, 'update']), [
            'group' => 'general',
            'values' => [
                'phone' => '966500000000',
            ],
            'is_public' => [
                'phone' => false,
            ],
        ])
        ->assertRedirect();

    expect((bool) Setting::query()->where('key', 'phone')->value('is_public'))->toBeTrue();

    $this->getJson(action([ApiSettingController::class, 'settings']))
        ->assertSuccessful()
        ->assertJsonPath('data.phone', '966500000000');
});
