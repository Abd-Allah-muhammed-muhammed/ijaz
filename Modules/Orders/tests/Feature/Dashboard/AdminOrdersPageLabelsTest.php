<?php

use Modules\Orders\Enums\OrderStatusEnum;

/**
 * Admin Orders index copy is client-rendered via i18next `t()`, so we assert:
 * 1) Index.tsx wires translated keys (not hardcoded English JSX / raw enum values), and
 * 2) those keys resolve to real translations for every supported locale.
 */
dataset('admin_orders_search_placeholder_locales', [
    'ar' => ['ar', 'بحث'],
    'en' => ['en', 'Search'],
    'hi' => ['hi', 'खोज'],
    'ur' => ['ur', 'تلاش کریں'],
]);

test('Admin Orders page renders a translated search placeholder for ar/en/hi/ur, not hardcoded "Search"', function (string $locale, string $search): void {
    $source = file_get_contents(resource_path('js/apps/admin/pages/Orders/Index.tsx'));

    expect($source)->not->toBeFalse();

    expect($source)
        ->toContain("placeholder={t('search')}")
        ->not->toContain("placeholder='Search'")
        ->not->toContain('placeholder="Search"');

    expect(__('search', [], $locale))->toBe($search);

    if ($locale !== 'en') {
        expect(__('search', [], $locale))->not->toBe('Search');
    }
})->with('admin_orders_search_placeholder_locales');

test('Admin Orders status filter dropdown renders translated labels for ALL OrderStatusEnum values including hold and payment_completed — not raw enum keys', function (string $locale): void {
    $source = file_get_contents(resource_path('js/apps/admin/pages/Orders/Index.tsx'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('{t(status)}');

    foreach (OrderStatusEnum::cases() as $status) {
        $label = __($status->value, [], $locale);

        expect($label)
            ->toBeString()
            ->not->toBeEmpty()
            ->not->toBe($status->value, "Missing translation for [{$status->value}] in locale [{$locale}]");
    }
})->with(['ar', 'en', 'hi', 'ur']);
