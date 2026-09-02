<?php

use Modules\Orders\Enums\OrderStatusEnum;

/**
 * Admin Orders index copy is server-driven for status labels (selects.statuses),
 * matching the Guarantor Index pattern.
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

test('Admin Orders status filter dropdown uses server-provided selects.statuses labels, not a stale client-side enum import', function (): void {
    $source = file_get_contents(resource_path('js/apps/admin/pages/Orders/Index.tsx'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('selects.statuses.map')
        ->and($source)->toContain('{status.label}')
        ->and($source)->not->toContain('@/Enums/Order')
        ->and($source)->not->toContain('{t(status)}');

    foreach (OrderStatusEnum::cases() as $status) {
        expect(__($status->value))
            ->toBeString()
            ->not->toBeEmpty()
            ->not->toBe($status->value);
    }
});
