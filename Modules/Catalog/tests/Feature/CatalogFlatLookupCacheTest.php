<?php

use App\Http\Controllers\General\ReactSelectController;
use App\Http\Resources\General\ReactSelectResource;
use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Enumerable;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Catalog\Actions\CarBrand\DeleteCarBrandAction;
use Modules\Catalog\Actions\CarBrand\UpdateStatusCarBrandAction;
use Modules\Catalog\Actions\CarType\DeleteCarTypeAction;
use Modules\Catalog\Actions\ElectronicBrand\UpdateStatusElectronicBrandAction;
use Modules\Catalog\Actions\PropertyType\DeletePropertyTypeAction;
use Modules\Catalog\Actions\PropertyType\UpdateStatusPropertyTypeAction;
use Modules\Catalog\Http\Controllers\Api\V1\ElectronicBrandController;
use Modules\Catalog\Http\Controllers\General\CatalogSelectController;
use Modules\Catalog\Http\Resources\Api\ElectronicBrandResource;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarType;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\Models\PropertyType;
use Modules\Catalog\Services\CarBrandService;
use Modules\Catalog\Services\CarTypeService;
use Modules\Catalog\Services\ElectronicBrandService;
use Modules\Catalog\Services\PropertyTypeService;
use Modules\Marketplace\Actions\Skill\DeleteSkillAction;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\Skill;
use Modules\Marketplace\Services\SkillService;

beforeEach(function (): void {
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();

    LookupCache::forgetAllLocales('car-brands:all');
    LookupCache::forgetAllLocales('property-types:all');
    LookupCache::forgetAllLocales('electronic-brands:all');
    LookupCache::forgetScopedAllLocales('car-types:by-brand', 0);
    LookupCache::forgetScopedAllLocales('skills:by-category', 0);

    app()->setLocale('en');
});

/**
 * Recursively assert identical PHP types (gettype / get_class) and equal values.
 */
function assertFlatCatalogIdenticalTyped(mixed $expected, mixed $actual, string $path = 'root'): void
{
    expect(gettype($actual))->toBe(gettype($expected), "gettype mismatch at {$path}");

    if (is_object($expected)) {
        expect(get_class($actual))->toBe(get_class($expected), "get_class mismatch at {$path}");

        if ($expected instanceof Enumerable) {
            assertFlatCatalogIdenticalTyped($expected->all(), $actual->all(), $path);

            return;
        }

        if ($expected instanceof JsonSerializable) {
            assertFlatCatalogIdenticalTyped($expected->jsonSerialize(), $actual->jsonSerialize(), $path);

            return;
        }
    }

    if (is_array($expected)) {
        expect(array_keys($actual))->toBe(array_keys($expected), "array keys mismatch at {$path}");

        foreach ($expected as $key => $value) {
            assertFlatCatalogIdenticalTyped($value, $actual[$key], "{$path}.{$key}");
        }

        return;
    }

    expect($actual)->toBe($expected, "value mismatch at {$path}");
}

function assertFlatCatalogSha256Identical(string $cold, string $warm, string $label): void
{
    expect(hash('sha256', $warm))->toBe(hash('sha256', $cold), "SHA-256 mismatch for {$label}");
    expect($warm)->toBe($cold, "byte mismatch for {$label}");
}

function seedElectronicBrand(string $name = 'Sony Cache', bool $active = true): ElectronicBrand
{
    $brand = ElectronicBrand::query()->create([
        'image' => null,
        'is_active' => $active,
    ]);
    $brand->translations()->create([
        'locale' => 'en',
        'name' => $name,
    ]);

    return $brand->fresh(['translation']) ?? $brand;
}

function seedSkillForCategory(Category $category, string $title = 'Welding'): Skill
{
    $skill = Skill::query()->create(['category_id' => $category->id]);
    $skill->translations()->create([
        'locale' => 'en',
        'title' => $title,
    ]);

    LookupCache::forgetScopedAllLocales('skills:by-category', $category->id);

    return $skill->fresh(['translation']) ?? $skill;
}

test('car-brands:all listForSelect is byte-for-byte identical cold vs warm', function (): void {
    $brand = CarBrand::factory()->create();
    LookupCache::forgetAllLocales('car-brands:all');

    $service = app(CarBrandService::class);

    $cold = $service->listForSelect();
    $warm = $service->listForSelect();

    expect($cold)->toBeInstanceOf(EloquentCollection::class)
        ->and($cold->first())->toBeInstanceOf(CarBrand::class)
        ->and(get_class($warm))->toBe(get_class($cold))
        ->and(get_class($warm->first()))->toBe(get_class($cold->first()));

    $request = Request::create('/general/car-brands', 'GET');
    $coldResolved = ReactSelectResource::collection($cold)->resolve($request);
    $warmResolved = ReactSelectResource::collection($warm)->resolve($request);
    expect(json_encode($warmResolved))->toBe(json_encode($coldResolved));
    assertFlatCatalogIdenticalTyped($coldResolved, $warmResolved, 'car-brands.select');

    $coldHttp = $this->getJson(action([CatalogSelectController::class, 'carBrands']))->assertSuccessful();
    $warmHttp = $this->getJson(action([CatalogSelectController::class, 'carBrands']))->assertSuccessful();
    assertFlatCatalogSha256Identical($coldHttp->getContent(), $warmHttp->getContent(), 'car-brands.http');

    expect($cold)->toHaveCount(1);
    app(DeleteCarBrandAction::class)->handle($brand);
    expect($service->listForSelect())->toHaveCount(0);
});

test('car-types:by-brand listForSelect is byte-for-byte identical for scoped brand', function (): void {
    $brand = CarBrand::factory()->create();
    $type = CarType::factory()->create(['car_brand_id' => $brand->id]);
    LookupCache::forgetScopedAllLocales('car-types:by-brand', $brand->id);
    LookupCache::forgetScopedAllLocales('car-types:by-brand', 0);

    $service = app(CarTypeService::class);

    $cold = $service->listForSelect(null, $brand->id);
    $warm = $service->listForSelect(null, $brand->id);

    expect($cold)->toBeInstanceOf(EloquentCollection::class)
        ->and($cold->first())->toBeInstanceOf(CarType::class)
        ->and(get_class($warm))->toBe(get_class($cold));

    $request = Request::create('/general/car-types', 'GET');
    $coldResolved = ReactSelectResource::collection($cold)->resolve($request);
    $warmResolved = ReactSelectResource::collection($warm)->resolve($request);
    expect(json_encode($warmResolved))->toBe(json_encode($coldResolved));
    assertFlatCatalogIdenticalTyped($coldResolved, $warmResolved, 'car-types.select');

    $coldHttp = $this->getJson(action([CatalogSelectController::class, 'carTypes'], ['car_brand_id' => $brand->id]))
        ->assertSuccessful();
    $warmHttp = $this->getJson(action([CatalogSelectController::class, 'carTypes'], ['car_brand_id' => $brand->id]))
        ->assertSuccessful();
    assertFlatCatalogSha256Identical($coldHttp->getContent(), $warmHttp->getContent(), 'car-types.http');

    expect($cold)->toHaveCount(1);
    app(DeleteCarTypeAction::class)->handle($type);
    expect($service->listForSelect(null, $brand->id))->toHaveCount(0);
});

test('property-types:all listForSelect is byte-for-byte identical cold vs warm', function (): void {
    $type = PropertyType::factory()->create();
    LookupCache::forgetAllLocales('property-types:all');

    $service = app(PropertyTypeService::class);

    $cold = $service->listForSelect();
    $warm = $service->listForSelect();

    expect($cold)->toBeInstanceOf(EloquentCollection::class)
        ->and($cold->first())->toBeInstanceOf(PropertyType::class);

    $request = Request::create('/general/property-types', 'GET');
    $coldResolved = ReactSelectResource::collection($cold)->resolve($request);
    $warmResolved = ReactSelectResource::collection($warm)->resolve($request);
    expect(json_encode($warmResolved))->toBe(json_encode($coldResolved));
    assertFlatCatalogIdenticalTyped($coldResolved, $warmResolved, 'property-types.select');

    $coldHttp = $this->getJson(action([CatalogSelectController::class, 'propertyTypes']))->assertSuccessful();
    $warmHttp = $this->getJson(action([CatalogSelectController::class, 'propertyTypes']))->assertSuccessful();
    assertFlatCatalogSha256Identical($coldHttp->getContent(), $warmHttp->getContent(), 'property-types.http');

    expect($cold)->toHaveCount(1);
    app(DeletePropertyTypeAction::class)->handle($type);
    expect($service->listForSelect())->toHaveCount(0);
});

test('electronic-brands:all select and API getAll share cache and stay byte-identical', function (): void {
    seedElectronicBrand('Active Brand', true);
    seedElectronicBrand('Inactive Brand', false);
    LookupCache::forgetAllLocales('electronic-brands:all');

    $service = app(ElectronicBrandService::class);

    $coldSelect = $service->listForSelect();
    $warmSelect = $service->listForSelect();
    $coldApi = $service->getAll(Request::create('/api/v1/catalog/electronic-brands', 'GET'));
    $warmApi = $service->getAll(Request::create('/api/v1/catalog/electronic-brands', 'GET'));

    expect($coldSelect)->toBeInstanceOf(EloquentCollection::class)
        ->and($coldSelect)->toHaveCount(1)
        ->and($coldSelect->first())->toBeInstanceOf(ElectronicBrand::class)
        ->and(get_class($warmSelect))->toBe(get_class($coldSelect));

    $request = Request::create('/general/electronic-brands', 'GET');
    $coldSelectResolved = ReactSelectResource::collection($coldSelect)->resolve($request);
    $warmSelectResolved = ReactSelectResource::collection($warmSelect)->resolve($request);
    expect(json_encode($warmSelectResolved))->toBe(json_encode($coldSelectResolved));
    assertFlatCatalogIdenticalTyped($coldSelectResolved, $warmSelectResolved, 'electronic-brands.select');

    $apiRequest = Request::create('/api/v1/catalog/electronic-brands', 'GET');
    $coldApiResolved = ElectronicBrandResource::collection($coldApi)->resolve($apiRequest);
    $warmApiResolved = ElectronicBrandResource::collection($warmApi)->resolve($apiRequest);
    expect(json_encode($warmApiResolved))->toBe(json_encode($coldApiResolved));
    assertFlatCatalogIdenticalTyped($coldApiResolved, $warmApiResolved, 'electronic-brands.api');

    $coldHttp = $this->getJson(action([CatalogSelectController::class, 'electronicBrands']))->assertSuccessful();
    $warmHttp = $this->getJson(action([CatalogSelectController::class, 'electronicBrands']))->assertSuccessful();
    assertFlatCatalogSha256Identical($coldHttp->getContent(), $warmHttp->getContent(), 'electronic-brands.select.http');

    $coldApiHttp = $this->getJson(action([ElectronicBrandController::class, 'index']))->assertSuccessful();
    $warmApiHttp = $this->getJson(action([ElectronicBrandController::class, 'index']))->assertSuccessful();
    assertFlatCatalogSha256Identical($coldApiHttp->getContent(), $warmApiHttp->getContent(), 'electronic-brands.api.http');
});

test('skills:by-category listForSelect is byte-for-byte identical for scoped category', function (): void {
    $category = Category::factory()->create();
    $skill = seedSkillForCategory($category, 'Plumbing');

    $service = app(SkillService::class);

    $cold = $service->listForSelect(null, $category->id);
    $warm = $service->listForSelect(null, $category->id);

    expect($cold)->toBeInstanceOf(EloquentCollection::class)
        ->and($cold)->toHaveCount(1)
        ->and($cold->first())->toBeInstanceOf(Skill::class)
        ->and(get_class($warm))->toBe(get_class($cold));

    $request = Request::create('/general/skills', 'GET');
    $coldResolved = ReactSelectResource::collection($cold)->resolve($request);
    $warmResolved = ReactSelectResource::collection($warm)->resolve($request);
    expect(json_encode($warmResolved))->toBe(json_encode($coldResolved));
    assertFlatCatalogIdenticalTyped($coldResolved, $warmResolved, 'skills.select');

    $coldHttp = $this->getJson(action([ReactSelectController::class, 'skills'], ['category_id' => $category->id]))
        ->assertSuccessful();
    $warmHttp = $this->getJson(action([ReactSelectController::class, 'skills'], ['category_id' => $category->id]))
        ->assertSuccessful();
    assertFlatCatalogSha256Identical($coldHttp->getContent(), $warmHttp->getContent(), 'skills.http');

    app(DeleteSkillAction::class)->handle($skill);
    expect($service->listForSelect(null, $category->id))->toHaveCount(0);
});

test('status toggles invalidate electronic-brands and property-types caches', function (): void {
    $electronic = seedElectronicBrand('Toggle Brand', true);
    $property = PropertyType::factory()->create(['is_active' => true]);
    LookupCache::forgetAllLocales('electronic-brands:all');
    LookupCache::forgetAllLocales('property-types:all');

    $electronicService = app(ElectronicBrandService::class);
    $propertyService = app(PropertyTypeService::class);

    expect($electronicService->listForSelect())->toHaveCount(1);
    expect($propertyService->listForSelect())->toHaveCount(1);

    app(UpdateStatusElectronicBrandAction::class)->handle($electronic, false);
    expect($electronicService->listForSelect())->toHaveCount(0);

    app(UpdateStatusPropertyTypeAction::class)->handle($property, false);
    // PropertyType select does not filter is_active — row remains visible after toggle.
    expect($propertyService->listForSelect())->toHaveCount(1);
});

test('car brand status toggle invalidates car-brands cache', function (): void {
    $brand = CarBrand::factory()->create(['is_active' => true]);
    LookupCache::forgetAllLocales('car-brands:all');

    $service = app(CarBrandService::class);
    expect($service->listForSelect())->toHaveCount(1);

    app(UpdateStatusCarBrandAction::class)->handle($brand, false);
    // Select does not filter is_active; invalidation still clears stale translations/payload.
    expect($service->listForSelect())->toHaveCount(1)
        ->and($service->listForSelect()->first()->is_active)->toBeFalse();
});
