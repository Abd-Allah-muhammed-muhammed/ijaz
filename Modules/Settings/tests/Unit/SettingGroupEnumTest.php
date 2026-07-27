<?php

use Modules\Settings\Enums\SettingGroupEnum;
use Modules\Settings\Models\Setting;

it('casts group to SettingGroupEnum and keeps null when column is null', function () {
    $setting = Setting::query()->create([
        'key' => 'enum_cast_probe',
        'content' => 'x',
        'group' => SettingGroupEnum::Wallet,
        'is_public' => false,
    ]);

    expect($setting->fresh()->group)->toBe(SettingGroupEnum::Wallet)
        ->and($setting->fresh()->group->value)->toBe('wallet');

    // Nullable cast: explicitly null group must stay null (not coerced to General).
    Setting::query()->whereKey($setting->id)->update(['group' => null]);

    expect($setting->fresh()->group)->toBeNull();
});

it('exposes case values in declaration order for dashboard tab order', function () {
    expect(array_column(SettingGroupEnum::cases(), 'value'))->toBe([
        'general',
        'wallet',
        'payment',
        'guarantor',
        'chat',
    ]);
});
