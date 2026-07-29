<?php

use Modules\Catalog\Http\Controllers\Dashboard\DeviceCategoryController;
use Modules\Catalog\Http\Controllers\Dashboard\ElectronicBrandController;
use Modules\Catalog\Http\Controllers\Dashboard\SpecializationController;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\Models\Specialization;

/**
 * @return array<string, array{title?: string, name?: string}>
 */
function catalogEditPrefillTranslations(string $field, string $prefix): array
{
    return [
        'en' => [$field => "{$prefix} EN"],
        'ar' => [$field => "{$prefix} AR"],
        'ur' => [$field => "{$prefix} UR"],
        'hi' => [$field => "{$prefix} HI"],
    ];
}

test('editing a device category returns populated translation data, not empty', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit deviceCategories']);

    $category = DeviceCategory::query()->create([
        'parent_id' => null,
        'icon' => null,
    ]);
    foreach (catalogEditPrefillTranslations('title', 'Keep Device') as $locale => $attrs) {
        $category->translations()->updateOrCreate(['locale' => $locale], $attrs);
    }

    $this->actingAs($admin, 'admin')
        ->get(action([DeviceCategoryController::class, 'edit'], $category))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/DeviceCategories/Edit')
            ->has('category.translations.en.title')
            ->where('category.translations.en.title', 'Keep Device EN')
            ->where('category.translations.ar.title', 'Keep Device AR')
        );
});

test('editing an electronic brand returns populated translation data, not empty', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit electronicBrands']);

    $brand = ElectronicBrand::query()->create([
        'image' => null,
        'is_active' => true,
    ]);
    foreach (catalogEditPrefillTranslations('name', 'Keep Brand') as $locale => $attrs) {
        $brand->translations()->updateOrCreate(['locale' => $locale], $attrs);
    }

    $this->actingAs($admin, 'admin')
        ->get(action([ElectronicBrandController::class, 'edit'], $brand))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/ElectronicBrands/Edit')
            ->has('electronicBrand.translations.en.name')
            ->where('electronicBrand.translations.en.name', 'Keep Brand EN')
            ->where('electronicBrand.translations.ar.name', 'Keep Brand AR')
        );
});

test('editing a specialization returns populated translation data, not empty', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit specializations']);

    $specialization = Specialization::factory()->create();
    foreach (catalogEditPrefillTranslations('title', 'Keep Spec') as $locale => $attrs) {
        $specialization->translations()->updateOrCreate(['locale' => $locale], $attrs);
    }

    $this->actingAs($admin, 'admin')
        ->get(action([SpecializationController::class, 'edit'], $specialization))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Specializations/Edit')
            ->has('specialization.translations.en.title')
            ->where('specialization.translations.en.title', 'Keep Spec EN')
            ->where('specialization.translations.ar.title', 'Keep Spec AR')
        );
});
