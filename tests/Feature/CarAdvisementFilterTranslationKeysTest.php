<?php

/**
 * Car Advertisements admin filter bar uses flat t('usage_status'), t('car_type'),
 * and t('car_brand') placeholders. Missing keys render as raw key strings.
 */
it('defines flat car advisement filter placeholder keys in every locale JSON source', function () {
    $keys = ['usage_status', 'car_type', 'car_brand'];
    $locales = ['en', 'ar', 'ur', 'hi'];

    foreach ($locales as $locale) {
        $path = lang_path("{$locale}.json");
        expect(file_exists($path))->toBeTrue();

        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($path), true);

        foreach ($keys as $key) {
            expect($data)->toHaveKey($key)
                ->and($data[$key])->toBeString()
                ->and($data[$key])->not->toBeEmpty()
                ->and($data[$key])->not->toBe($key);
        }
    }
});

it('generated frontend translations keep car advisement filter placeholder keys', function () {
    $this->artisan('make:js-translations')->assertSuccessful();

    $locales = ['en', 'ar', 'ur', 'hi'];
    $keys = ['usage_status', 'car_type', 'car_brand'];

    foreach ($locales as $locale) {
        $path = resource_path("js/lang/{$locale}.json");
        expect(file_exists($path))->toBeTrue();

        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($path), true);

        foreach ($keys as $key) {
            expect($data)->toHaveKey($key)
                ->and($data[$key])->toBeString()
                ->and($data[$key])->not->toBeEmpty()
                ->and($data[$key])->not->toBe($key);
        }

        // Existing plural / nested keys must remain usable (no collision overwrite).
        expect($data['car_types'])->toBeString()
            ->and($data['car_brands'])->toBeString()
            ->and($data['advisement'])->toBeArray()
            ->and($data['advisement']['usage_status'])->toBeArray();
    }
});
