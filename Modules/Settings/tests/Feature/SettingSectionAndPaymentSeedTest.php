<?php

use Database\Seeders\SettingsSeeder;
use Modules\Settings\Http\Resources\Dashboard\SettingResource;
use Modules\Settings\Models\Setting;

test('SettingsSeeder seeds a {driver}_fees row for every driver in config(payment.gateways), not just the current default', function () {
    $drivers = array_keys(config('payment.gateways', []));

    expect($drivers)->not->toBeEmpty();

    foreach ($drivers as $driver) {
        Setting::query()->where('key', "{$driver}_fees")->delete();
    }

    $this->seed(SettingsSeeder::class);

    foreach ($drivers as $driver) {
        $row = Setting::query()->where('key', "{$driver}_fees")->first();

        expect($row)->not->toBeNull()
            ->and($row->group?->value)->toBe('payment')
            ->and((bool) $row->is_public)->toBeFalse();
    }
});

test('paytabs_fees, rajhi_fees, and testing_fees all exist in settings after seeding', function () {
    $this->seed(SettingsSeeder::class);

    foreach (['paytabs_fees', 'rajhi_fees', 'testing_fees'] as $key) {
        expect(Setting::query()->where('key', $key)->exists())->toBeTrue();
    }
});

test('adding a new driver to config(payment.gateways) and re-running the seeder creates its fee row automatically, without any seeder code change', function () {
    config([
        'payment.gateways' => [
            ...config('payment.gateways', []),
            'future_gateway' => 'Modules\\Payment\\Gateways\\TestingGateway',
        ],
    ]);

    Setting::query()->where('key', 'future_gateway_fees')->delete();

    $this->seed(SettingsSeeder::class);

    expect(Setting::query()->where('key', 'future_gateway_fees')->exists())->toBeTrue()
        ->and(Setting::query()->where('key', 'future_gateway_fees')->first()?->group?->value)->toBe('payment');
});

test('re-running the seeder does not duplicate or reset existing driver fee values (upsert-safe, matching this session\'s established seeder pattern)', function () {
    $this->seed(SettingsSeeder::class);

    Setting::query()->where('key', 'testing_fees')->update(['content' => '42.5']);

    $this->seed(SettingsSeeder::class);

    expect(Setting::query()->where('key', 'testing_fees')->count())->toBe(1)
        ->and(Setting::query()->where('key', 'testing_fees')->value('content'))->toBe('42.5');
});

test('Settings model has a nullable section column', function () {
    $withSection = Setting::query()->create([
        'key' => 'section_probe_'.uniqid(),
        'content' => 'x',
        'group' => 'general',
        'section' => 'contact',
        'is_public' => false,
    ]);

    $withoutSection = Setting::query()->create([
        'key' => 'section_null_probe_'.uniqid(),
        'content' => 'y',
        'group' => 'general',
        'is_public' => false,
    ]);

    expect($withSection->fresh()->section)->toBe('contact')
        ->and($withoutSection->fresh()->section)->toBeNull();
});

test('SettingsSeeder assigns section values to general-tab contact/social fields (e.g. email/phone/whatsapp -> "contact"; facebook/instagram/tiktok/etc -> "social")', function () {
    $this->seed(SettingsSeeder::class);

    $contactKeys = ['email', 'phone', 'whatsapp', 'telegram'];
    $socialKeys = ['facebook', 'instagram', 'tiktok', 'snapchat', 'youtube', 'x'];

    foreach ($contactKeys as $key) {
        expect(Setting::query()->where('key', $key)->value('section'))->toBe('contact');
    }

    foreach ($socialKeys as $key) {
        expect(Setting::query()->where('key', $key)->value('section'))->toBe('social');
    }

    expect(Setting::query()->where('key', 'offer_note')->value('section'))->toBeNull();
});

test('a setting with no section value is still returned by the API/dashboard resource, with section null', function () {
    $setting = Setting::query()->create([
        'key' => 'section_resource_probe_'.uniqid(),
        'content' => 'visible',
        'group' => 'general',
        'section' => null,
        'is_public' => true,
    ]);

    $payload = SettingResource::make($setting->fresh())->resolve();

    expect($payload)->toHaveKey('section')
        ->and($payload['section'])->toBeNull()
        ->and($payload['key'])->toBe($setting->key)
        ->and($payload['content'])->toBe('visible');
});
