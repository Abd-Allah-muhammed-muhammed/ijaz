<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Http\Controllers\Dashboard\CarBrandController;
use Modules\Catalog\Http\Controllers\Dashboard\CarCategoryController;
use Modules\Catalog\Http\Controllers\Dashboard\CarTypeController;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;

function carTaxonomyTranslations(string $field, string $prefix): array
{
    return [
        'en' => [$field => "{$prefix} EN"],
        'ar' => [$field => "{$prefix} AR"],
        'ur' => [$field => "{$prefix} UR"],
        'hi' => [$field => "{$prefix} HI"],
    ];
}

test('creating a car category with translations persists successfully', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    Storage::fake('public');
    $admin = createGeoDashboardAdmin(['create carCategories']);

    $this->actingAs($admin, 'admin')
        ->post(action([CarCategoryController::class, 'store']), [
            'translations' => carTaxonomyTranslations('title', 'Sedan Category'),
            'parent_id' => null,
            'icon' => UploadedFile::fake()->image('icon.jpg'),
        ])
        ->assertRedirect(route('dashboard.car-categories.index'))
        ->assertSessionHasNoErrors();

    $category = CarCategory::query()->whereTranslation('title', 'Sedan Category EN')->first();
    expect($category)->not->toBeNull()
        ->and($category->translate('en')->title)->toBe('Sedan Category EN')
        ->and($category->translate('ar')->locale)->toBe('ar');
});

test('creating a car type with translations persists successfully', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    Storage::fake('public');
    $admin = createGeoDashboardAdmin(['create carTypes']);

    $brand = CarBrand::factory()->create();
    $brand->translations()->updateOrCreate(
        ['locale' => 'en'],
        ['name' => 'Brand For Type'],
    );

    $this->actingAs($admin, 'admin')
        ->post(action([CarTypeController::class, 'store']), [
            'translations' => carTaxonomyTranslations('name', 'Coupe Type'),
            'car_brand_id' => $brand->id,
            'is_active' => true,
            'image' => UploadedFile::fake()->image('type.jpg'),
        ])
        ->assertRedirect(route('dashboard.car-types.index'))
        ->assertSessionHasNoErrors();

    $type = CarType::query()->whereTranslation('name', 'Coupe Type EN')->first();
    expect($type)->not->toBeNull()
        ->and($type->translate('en')->name)->toBe('Coupe Type EN')
        ->and($type->translate('ar')->locale)->toBe('ar')
        ->and($type->car_brand_id)->toBe($brand->id);
});

test('creating a car brand with translations persists successfully', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    Storage::fake('public');
    $admin = createGeoDashboardAdmin(['create carBrands']);

    $this->actingAs($admin, 'admin')
        ->post(action([CarBrandController::class, 'store']), [
            'translations' => carTaxonomyTranslations('name', 'Toyota Brand'),
            'is_active' => true,
            'image' => UploadedFile::fake()->image('brand.jpg'),
        ])
        ->assertRedirect(route('dashboard.car-brands.index'))
        ->assertSessionHasNoErrors();

    $brand = CarBrand::query()->whereTranslation('name', 'Toyota Brand EN')->first();
    expect($brand)->not->toBeNull()
        ->and($brand->translate('en')->name)->toBe('Toyota Brand EN')
        ->and($brand->translate('ar')->locale)->toBe('ar');
});

test('editing a car category returns populated translation data, not empty', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit carCategories']);

    $category = CarCategory::factory()->create();
    foreach (carTaxonomyTranslations('title', 'Keep Category') as $locale => $attrs) {
        $category->translations()->updateOrCreate(['locale' => $locale], $attrs);
    }

    $this->actingAs($admin, 'admin')
        ->get(action([CarCategoryController::class, 'edit'], $category))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/CarCategories/Edit')
            ->has('category.translations.en.title')
            ->where('category.translations.en.title', 'Keep Category EN')
            ->where('category.translations.ar.title', 'Keep Category AR')
        );
});

test('editing a car type returns populated translation data, not empty', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit carTypes']);

    $brand = CarBrand::factory()->create();
    $type = CarType::factory()->create(['car_brand_id' => $brand->id]);
    foreach (carTaxonomyTranslations('name', 'Keep Type') as $locale => $attrs) {
        $type->translations()->updateOrCreate(['locale' => $locale], $attrs);
    }

    $this->actingAs($admin, 'admin')
        ->get(action([CarTypeController::class, 'edit'], $type))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/CarTypes/Edit')
            ->has('carType.translations.en.name')
            ->where('carType.translations.en.name', 'Keep Type EN')
            ->where('carType.translations.ar.name', 'Keep Type AR')
        );
});

test('editing a car brand returns populated translation data, not empty', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit carBrands']);

    $brand = CarBrand::factory()->create();
    foreach (carTaxonomyTranslations('name', 'Keep Brand') as $locale => $attrs) {
        $brand->translations()->updateOrCreate(['locale' => $locale], $attrs);
    }

    $this->actingAs($admin, 'admin')
        ->get(action([CarBrandController::class, 'edit'], $brand))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/CarBrands/Edit')
            ->has('carBrand.translations.en.name')
            ->where('carBrand.translations.en.name', 'Keep Brand EN')
            ->where('carBrand.translations.ar.name', 'Keep Brand AR')
        );
});
