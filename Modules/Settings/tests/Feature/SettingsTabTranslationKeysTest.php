<?php

/**
 * Guards against Settings tab labels calling t(rawGroup) when a group value
 * collides with a nested i18n object (e.g. the frontend guarantor namespace object).
 *
 * Source of truth is lang/*.json; resources/js/lang is generated via make:js-translations,
 * which merges lang/{locale}/guarantor.php as a nested "guarantor" object and would
 * overwrite any flat "guarantor" string — hence settings_tab_* keys.
 */
it('defines flat string settings_tab_* keys in every locale JSON source', function () {
    $groups = ['general', 'wallet', 'payment', 'guarantor', 'chat'];
    $locales = ['en', 'ar', 'ur', 'hi'];

    foreach ($locales as $locale) {
        $path = lang_path("{$locale}.json");
        expect(file_exists($path))->toBeTrue();

        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($path), true);

        foreach ($groups as $group) {
            $key = "settings_tab_{$group}";
            expect($data)->toHaveKey($key)
                ->and($data[$key])->toBeString()
                ->and($data[$key])->not->toBeEmpty();
        }

        expect(lang_path("{$locale}/guarantor.php"))->toBeFile();
    }
});

it('generated frontend translations keep nested guarantor and flat settings_tab keys', function () {
    $this->artisan('make:js-translations')->assertSuccessful();

    $path = resource_path('js/lang/en.json');
    expect(file_exists($path))->toBeTrue();

    /** @var array<string, mixed> $data */
    $data = json_decode((string) file_get_contents($path), true);

    expect($data['guarantor'])->toBeArray()
        ->and($data['settings_tab_guarantor'])->toBe('Guarantor')
        ->and($data['settings_tab_general'])->toBe('General')
        ->and($data['settings_tab_wallet'])->toBe('Wallet')
        ->and($data['settings_tab_payment'])->toBe('Payment')
        ->and($data['settings_tab_chat'])->toBe('Chat');
});

/**
 * Settings field labels use t(`settings.${row.key}`) against flat dotted keys so the
 * top-level "settings" page-title string is preserved (same collision class as settings_tab_*).
 */
it('defines flat settings.<key> field labels for every current settings row in all locales', function () {
    $settingKeys = [
        'chat_notes',
        'email',
        'facebook',
        'instagram',
        'offer_note',
        'phone',
        'snapchat',
        'telegram',
        'tiktok',
        'whatsapp',
        'x',
        'youtube',
        'guarantee_fee_percent',
        'guarantee_notes',
        'guarantor_first_installment_max_days',
        'min_withdraw_amount',
        'order_dispute_window_hours',
        'provider_registration_bonus_amount',
        'provider_registration_bonus_enabled',
    ];
    $locales = ['en', 'ar', 'ur', 'hi'];

    foreach ($locales as $locale) {
        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents(lang_path("{$locale}.json")), true);

        expect($data['settings'])->toBeString()
            ->and($data['banks'])->toBeString()
            ->and($data['banks'])->not->toBeEmpty();

        foreach ($settingKeys as $settingKey) {
            $key = "settings.{$settingKey}";
            expect($data)->toHaveKey($key)
                ->and($data[$key])->toBeString()
                ->and($data[$key])->not->toBeEmpty()
                ->and($data[$key])->not->toBe($settingKey);
        }
    }
});
