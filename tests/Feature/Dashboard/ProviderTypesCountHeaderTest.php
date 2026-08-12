<?php

/**
 * Provider Types table headers are client-rendered via i18next `t()`. The raw
 * slug PROVIDERS_COUNT on screen means the header used a missing/mismatched key
 * that the table's text-uppercase CSS then uppercased.
 *
 * @return array{locale: string, provider_count: string}
 */
dataset('provider_types_count_header_locales', [
    'ar' => ['ar', 'عدد المزودين'],
    'hi' => ['hi', 'प्रदाताओं की संख्या'],
    'ur' => ['ur', 'فراہم کنندگان کی تعداد'],
    'en' => ['en', 'Provider Count'],
]);

test('Provider Types dashboard table renders a translated "Providers Count" column header for ar/en/hi/ur, not the raw key PROVIDERS_COUNT', function (string $locale, string $providerCount): void {
    $source = file_get_contents(resource_path('js/apps/admin/pages/ProviderTypes/Index.tsx'));

    expect($source)->not->toBeFalse();

    // Must use the existing provider_count key (same pattern as name/actions), not the missing providers_count slug.
    expect($source)
        ->toContain("t('provider_count')")
        ->not->toContain("t('providers_count')")
        ->not->toContain('PROVIDERS_COUNT')
        ->not->toContain('Providers Count');

    expect(__('provider_count', [], $locale))->toBe($providerCount)
        ->and(__('provider_count', [], $locale))->not->toBe('providers_count')
        ->and(__('provider_count', [], $locale))->not->toBe('PROVIDERS_COUNT');

    if ($locale !== 'en') {
        expect(__('provider_count', [], $locale))->not->toBe('Provider Count');
    }
})->with('provider_types_count_header_locales');
