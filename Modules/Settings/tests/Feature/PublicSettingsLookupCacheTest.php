<?php

use App\Support\LookupCache;
use Modules\Settings\Actions\Setting\ListPublicSettingsAction;
use Modules\Settings\Actions\Setting\UpdateSettingsAction;
use Modules\Settings\DTOs\UpdateSettingsDTO;
use Modules\Settings\Http\Controllers\Api\V1\SettingController as ApiSettingController;
use Modules\Settings\Models\Setting;

beforeEach(function (): void {
    cache()->forget('settings');
    app()->forgetInstance('settings');
    LookupCache::forget('settings:public');

    Setting::query()->updateOrCreate(
        ['key' => 'phone'],
        ['content' => '966500000000', 'group' => 'general', 'is_public' => true],
    );
    Setting::query()->updateOrCreate(
        ['key' => 'email'],
        ['content' => 'info@ijaz.sa', 'group' => 'general', 'is_public' => true],
    );
});

/**
 * Recursively assert identical PHP types (gettype / get_class) and values.
 */
function assertIdenticalTypedStructure(mixed $expected, mixed $actual, string $path = 'root'): void
{
    expect(gettype($actual))->toBe(gettype($expected), "gettype mismatch at {$path}");

    if (is_object($expected)) {
        expect(get_class($actual))->toBe(get_class($expected), "get_class mismatch at {$path}");
    }

    if (is_array($expected)) {
        expect(array_keys($actual))->toBe(array_keys($expected), "array keys mismatch at {$path}");

        foreach ($expected as $key => $value) {
            assertIdenticalTypedStructure($value, $actual[$key], "{$path}.{$key}");
        }

        return;
    }

    expect($actual)->toBe($expected, "value mismatch at {$path}");
}

test('public settings API response is byte-for-byte identical whether served from cache or fresh query', function (): void {
    $action = app(ListPublicSettingsAction::class);

    $coldPayload = $action->handle();
    expect($coldPayload)->toBeArray()
        ->and(gettype($coldPayload))->toBe('array');

    foreach ($coldPayload as $value) {
        expect(gettype($value))->toBe('string');
    }

    $cold = $this->getJson(action([ApiSettingController::class, 'settings']))
        ->assertSuccessful();

    $coldBody = $cold->getContent();
    $coldJson = $cold->json();

    $warmPayload = $action->handle();
    assertIdenticalTypedStructure($coldPayload, $warmPayload, 'action.handle');

    $warm = $this->getJson(action([ApiSettingController::class, 'settings']))
        ->assertSuccessful();

    $warmBody = $warm->getContent();
    $warmJson = $warm->json();

    expect($warmBody)->toBe($coldBody);
    assertIdenticalTypedStructure($coldJson, $warmJson, 'http.json');
});

test('UpdateSettingsAction invalidates the public settings lookup cache', function (): void {
    $list = app(ListPublicSettingsAction::class);
    $update = app(UpdateSettingsAction::class);

    expect($list->handle()['phone'])->toBe('966500000000');

    $update->handle(new UpdateSettingsDTO(
        values: ['phone' => '966511111111'],
    ));

    expect($list->handle()['phone'])->toBe('966511111111');
});
