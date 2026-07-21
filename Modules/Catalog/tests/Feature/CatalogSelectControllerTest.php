<?php

use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Catalog\Http\Controllers\General\CatalogSelectController;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\Models\PropertiyCategory;
use Modules\Catalog\Models\PropertyType;
use Modules\Catalog\Models\Specialization;
use Tests\TestCase;

beforeEach(function (): void {
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
});

/**
 * Regression lock for CatalogSelectController Phase 3 extraction.
 * Asserts ReactSelect label/value shape and domain-specific filters.
 */
it('returns property types as react-select options', function (): void {
    /** @var TestCase $this */
    $type = PropertyType::factory()->create();
    $type->translations()->where('locale', 'en')->update(['name' => 'Villa Select']);

    $response = $this->getJson(action([CatalogSelectController::class, 'propertyTypes']));

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['label', 'value'],
            ],
        ])
        ->assertJsonFragment([
            'label' => 'Villa Select',
            'value' => (string) $type->id,
        ]);
});

it('returns property categories as react-select options', function (): void {
    /** @var TestCase $this */
    $category = PropertiyCategory::factory()->create();
    $category->translations()->where('locale', 'en')->update(['title' => 'Residential Select']);

    $response = $this->getJson(action([CatalogSelectController::class, 'propertyCategories']));

    $response->assertOk()
        ->assertJsonFragment([
            'label' => 'Residential Select',
            'value' => (string) $category->id,
        ]);
});

it('returns car categories as react-select options', function (): void {
    /** @var TestCase $this */
    $category = CarCategory::factory()->create();
    $category->translations()->where('locale', 'en')->update(['title' => 'Passenger Select']);

    $response = $this->getJson(action([CatalogSelectController::class, 'carCategories']));

    $response->assertOk()
        ->assertJsonFragment([
            'label' => 'Passenger Select',
            'value' => (string) $category->id,
        ]);
});

it('returns car brands as react-select options', function (): void {
    /** @var TestCase $this */
    $brand = CarBrand::factory()->create();
    $brand->translations()->where('locale', 'en')->update(['name' => 'Toyota Select']);

    $response = $this->getJson(action([CatalogSelectController::class, 'carBrands']));

    $response->assertOk()
        ->assertJsonFragment([
            'label' => 'Toyota Select',
            'value' => (string) $brand->id,
        ]);
});

it('filters car types by car_brand_id', function (): void {
    /** @var TestCase $this */
    $brandA = CarBrand::factory()->create();
    $brandB = CarBrand::factory()->create();

    $typeA = CarType::factory()->create(['car_brand_id' => $brandA->id]);
    $typeA->translations()->where('locale', 'en')->update(['name' => 'Sedan A']);

    $typeB = CarType::factory()->create(['car_brand_id' => $brandB->id]);
    $typeB->translations()->where('locale', 'en')->update(['name' => 'Sedan B']);

    $response = $this->getJson(action(
        [CatalogSelectController::class, 'carTypes'],
        ['car_brand_id' => $brandA->id],
    ));

    $response->assertOk()
        ->assertJsonFragment(['label' => 'Sedan A', 'value' => (string) $typeA->id])
        ->assertJsonMissing(['value' => (string) $typeB->id]);
});

it('returns only active electronic brands', function (): void {
    /** @var TestCase $this */
    $active = ElectronicBrand::create(['image' => null, 'is_active' => true]);
    $active->translations()->createMany([
        ['locale' => 'en', 'name' => 'Active Brand'],
        ['locale' => 'ar', 'name' => 'نشط'],
    ]);

    $inactive = ElectronicBrand::create(['image' => null, 'is_active' => false]);
    $inactive->translations()->createMany([
        ['locale' => 'en', 'name' => 'Inactive Brand'],
        ['locale' => 'ar', 'name' => 'غير نشط'],
    ]);

    $response = $this->getJson(action([CatalogSelectController::class, 'electronicBrands']));

    $response->assertOk()
        ->assertJsonFragment(['label' => 'Active Brand', 'value' => (string) $active->id])
        ->assertJsonMissing(['value' => (string) $inactive->id]);
});

it('filters specializations by parent_id', function (): void {
    /** @var TestCase $this */
    $parent = Specialization::factory()->create();
    $parent->translations()->where('locale', 'en')->update(['title' => 'Parent Spec']);

    $child = Specialization::factory()->create(['parent_id' => $parent->id]);
    $child->translations()->where('locale', 'en')->update(['title' => 'Child Spec']);

    $other = Specialization::factory()->create();
    $other->translations()->where('locale', 'en')->update(['title' => 'Other Spec']);

    $response = $this->getJson(action(
        [CatalogSelectController::class, 'specializations'],
        ['parent_id' => $parent->id],
    ));

    $response->assertOk()
        ->assertJsonFragment(['label' => 'Child Spec', 'value' => (string) $child->id])
        ->assertJsonMissing(['value' => (string) $other->id])
        ->assertJsonMissing(['value' => (string) $parent->id]);
});

it('returns device categories as react-select options', function (): void {
    /** @var TestCase $this */
    $category = DeviceCategory::create(['parent_id' => null, 'icon' => null]);
    $category->translations()->createMany([
        ['locale' => 'en', 'title' => 'Phones Select'],
        ['locale' => 'ar', 'title' => 'هواتف'],
    ]);

    $response = $this->getJson(action([CatalogSelectController::class, 'deviceCategories']));

    $response->assertOk()
        ->assertJsonFragment([
            'label' => 'Phones Select',
            'value' => (string) $category->id,
        ]);
});
