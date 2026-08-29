<?php

use Database\Seeders\SettingsSeeder;
use Modules\Payment\Services\PaymentService;
use Modules\Settings\Http\Resources\Dashboard\SettingResource;
use Modules\Settings\Models\Setting;

test('SettingsSeeder seeds the active payment driver fee key under group=payment', function () {
    $driverFeesKey = app(PaymentService::class)->getDefaultDriver().'_fees';

    Setting::query()->where('key', $driverFeesKey)->delete();

    $this->seed(SettingsSeeder::class);

    $row = Setting::query()->where('key', $driverFeesKey)->first();

    expect($row)->not->toBeNull()
        ->and($row->group?->value)->toBe('payment')
        ->and($row->content)->not->toBeEmpty()
        ->and((bool) $row->is_public)->toBeFalse();
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
