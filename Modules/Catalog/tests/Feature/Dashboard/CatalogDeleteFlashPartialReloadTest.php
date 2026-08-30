<?php

/**
 * Regression: Car Brands / Car Types / Property Types delete used
 * `only: ['rows']`, which excluded shared Inertia `flash` so ToastEffect
 * never showed the backend success/error message.
 *
 * Fix: `only: ['rows', 'flash']` — keep partial reload, include flash.
 */

use Modules\Catalog\Http\Controllers\Dashboard\CarBrandController;
use Modules\Catalog\Http\Controllers\Dashboard\CarTypeController;
use Modules\Catalog\Http\Controllers\Dashboard\PropertyTypeController;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarType;
use Modules\Catalog\Models\PropertyType;

test('deleting a Car Brand shows a success toast after confirmation', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete carBrands']);
    $brand = CarBrand::factory()->create();

    $this->actingAs($admin, 'admin')
        ->delete(action([CarBrandController::class, 'destroy'], $brand))
        ->assertRedirect(route('dashboard.car-brands.index'))
        ->assertSessionHas('success', __('data deleted successfully'));

    expect(CarBrand::query()->whereKey($brand->id)->exists())->toBeFalse();
});

test('deleting a Car Type shows a success toast after confirmation', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete carTypes']);
    $type = CarType::factory()->create();

    $this->actingAs($admin, 'admin')
        ->delete(action([CarTypeController::class, 'destroy'], $type))
        ->assertRedirect(route('dashboard.car-types.index'))
        ->assertSessionHas('success', __('data deleted successfully'));

    expect(CarType::query()->whereKey($type->id)->exists())->toBeFalse();
});

test('deleting a Property Type shows a success toast after confirmation', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete propertyTypes']);
    $propertyType = PropertyType::factory()->create();

    $this->actingAs($admin, 'admin')
        ->delete(action([PropertyTypeController::class, 'destroy'], $propertyType))
        ->assertRedirect(route('dashboard.property-types.index'))
        ->assertSessionHas('success', __('data deleted successfully'));

    expect(PropertyType::query()->whereKey($propertyType->id)->exists())->toBeFalse();
});

test('the row list still updates correctly via the partial reload — regression, only adding flash to the reload set, not removing the partial-reload optimization', function (): void {
    $pages = [
        resource_path('js/apps/admin/pages/CarBrands/Index.tsx'),
        resource_path('js/apps/admin/pages/CarTypes/Index.tsx'),
        resource_path('js/apps/admin/pages/PropertyTypes/Index.tsx'),
    ];

    foreach ($pages as $path) {
        expect(file_exists($path))->toBeTrue("Missing [{$path}]");

        $source = (string) file_get_contents($path);

        expect($source)->toContain('router.delete(');

        // Delete visit must include flash alongside rows (ToastEffect reads flash).
        expect(preg_match(
            "/router\.delete\([\s\S]*?only:\s*\[\s*'rows'\s*,\s*'flash'\s*\]/",
            $source
        ))->toBe(1, "Expected delete partial reload with flash in [{$path}]");

        // Must not regress to rows-only on the delete call.
        expect(preg_match(
            "/router\.delete\([\s\S]*?only:\s*\[\s*'rows'\s*\]/",
            $source
        ))->toBe(0, "Delete must not use only: ['rows'] without flash in [{$path}]");
    }
});
