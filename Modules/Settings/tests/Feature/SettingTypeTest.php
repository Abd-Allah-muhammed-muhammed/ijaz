<?php

use Database\Seeders\SettingsSeeder;
use Modules\Settings\Enums\SettingTypeEnum;
use Modules\Settings\Models\Setting;

test('Setting model has a type column defaulting to text for new/existing settings without an explicit type', function () {
    $created = Setting::query()->create([
        'key' => 'type_default_probe_'.uniqid(),
        'content' => 'short',
        'group' => 'general',
        'is_public' => false,
    ]);

    expect($created->fresh()->type)->toBe(SettingTypeEnum::Text)
        ->and($created->fresh()->type->value)->toBe('text')
        ->and($created->getAttributes()['type'] ?? null)->not->toBeNull();

    // Existing rows that omit type on updateOrCreate still resolve to text via column default / cast.
    $existing = Setting::query()->updateOrCreate(
        ['key' => 'type_upsert_probe_'.uniqid()],
        ['content' => 'x', 'group' => 'general', 'is_public' => false],
    );

    expect($existing->fresh()->type)->toBe(SettingTypeEnum::Text);
});

test('offer_note, chat_notes, and guarantee_notes are seeded with type=textarea', function () {
    // Simulate already-seeded rows that predate the type column (defaulted to text).
    foreach (['offer_note', 'chat_notes', 'guarantee_notes'] as $key) {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'content' => 'placeholder',
                'group' => 'general',
                'is_public' => true,
                'type' => SettingTypeEnum::Text,
            ],
        );
    }

    $this->seed(SettingsSeeder::class);

    foreach (['offer_note', 'chat_notes', 'guarantee_notes'] as $key) {
        expect(Setting::query()->where('key', $key)->first()?->type)
            ->toBe(SettingTypeEnum::Textarea);
    }
});

test('all other existing settings keys default to type=text', function () {
    $this->seed(SettingsSeeder::class);

    $textareaKeys = ['offer_note', 'chat_notes', 'guarantee_notes'];

    $others = Setting::query()
        ->whereNotIn('key', $textareaKeys)
        ->get();

    expect($others)->not->toBeEmpty();

    foreach ($others as $setting) {
        expect($setting->type)->toBe(SettingTypeEnum::Text);
    }
});

test('SettingTypeEnum handles text and textarea correctly, extensible for future types', function () {
    expect(SettingTypeEnum::Text->value)->toBe('text')
        ->and(SettingTypeEnum::Textarea->value)->toBe('textarea')
        ->and(array_column(SettingTypeEnum::cases(), 'value'))->toBe(['text', 'textarea'])
        ->and(SettingTypeEnum::tryFrom('text'))->toBe(SettingTypeEnum::Text)
        ->and(SettingTypeEnum::tryFrom('textarea'))->toBe(SettingTypeEnum::Textarea)
        ->and(SettingTypeEnum::tryFrom('boolean'))->toBeNull();
});
