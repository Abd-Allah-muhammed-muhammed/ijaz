<?php

use Modules\Catalog\Http\Controllers\Dashboard\CarCategoryController;
use Modules\Catalog\Http\Controllers\Dashboard\DeviceCategoryController;
use Modules\Catalog\Http\Controllers\Dashboard\PropertyCategoryController;
use Modules\Catalog\Http\Controllers\Dashboard\SpecializationController;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\PropertyCategory;
use Modules\Catalog\Models\Specialization;

test('deleting a device category with subcategories shows a clean translated error, not a 500', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete deviceCategories']);

    $parent = DeviceCategory::query()->create(['parent_id' => null, 'icon' => null]);
    DeviceCategory::query()->create(['parent_id' => $parent->id, 'icon' => null]);

    $expected = __('this category has subcategories');

    $this->actingAs($admin, 'admin')
        ->from(route('dashboard.device-categories.index'))
        ->delete(action([DeviceCategoryController::class, 'destroy'], $parent))
        ->assertRedirect(route('dashboard.device-categories.index'))
        ->assertSessionHas('error', $expected);

    expect($expected)->not->toBe('this category has subcategories')
        ->and(DeviceCategory::query()->whereKey($parent->id)->exists())->toBeTrue();
});

test('deleting a car category with subcategories shows a clean translated error, not a 500', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete carCategories']);

    $parent = CarCategory::factory()->create();
    CarCategory::factory()->create(['parent_id' => $parent->id]);

    $expected = __('this category has subcategories');

    $this->actingAs($admin, 'admin')
        ->from(route('dashboard.car-categories.index'))
        ->delete(action([CarCategoryController::class, 'destroy'], $parent))
        ->assertRedirect(route('dashboard.car-categories.index'))
        ->assertSessionHas('error', $expected);

    expect($expected)->not->toBe('this category has subcategories')
        ->and(CarCategory::query()->whereKey($parent->id)->exists())->toBeTrue();
});

test('deleting a specialization with subspecializations shows a clean translated error, not a 500', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete specializations']);

    $parent = Specialization::factory()->create();
    Specialization::factory()->create(['parent_id' => $parent->id]);

    $expected = __('this specialization has subspecializations');

    $this->actingAs($admin, 'admin')
        ->from(route('dashboard.specializations.index'))
        ->delete(action([SpecializationController::class, 'destroy'], $parent))
        ->assertRedirect(route('dashboard.specializations.index'))
        ->assertSessionHas('error', $expected);

    expect($expected)->not->toBe('this specialization has subspecializations')
        ->and(Specialization::query()->whereKey($parent->id)->exists())->toBeTrue();
});

test('deleting a property category with subcategories shows a clean translated error, not a 500', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete propertyCategories']);

    $parent = PropertyCategory::factory()->create();
    PropertyCategory::factory()->create(['parent_id' => $parent->id]);

    $expected = __('this category has subcategories');

    $this->actingAs($admin, 'admin')
        ->from(route('dashboard.property-categories.index'))
        ->delete(action([PropertyCategoryController::class, 'destroy'], $parent))
        ->assertRedirect(route('dashboard.property-categories.index'))
        ->assertSessionHas('error', $expected);

    expect($expected)->not->toBe('this category has subcategories')
        ->and(PropertyCategory::query()->whereKey($parent->id)->exists())->toBeTrue();
});
