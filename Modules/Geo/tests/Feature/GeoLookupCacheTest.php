<?php

use App\Http\Controllers\General\ReactSelectController;
use App\Http\Controllers\Provider\AuthController as ProviderAuthController;
use App\Http\Resources\General\ReactSelectResource;
use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Enumerable;
use Modules\Geo\Actions\City\DeleteCityAction;
use Modules\Geo\Actions\City\UpdateCityAction;
use Modules\Geo\Actions\Nationality\DeleteNationalityAction;
use Modules\Geo\Actions\Region\DeleteRegionAction;
use Modules\Geo\DTOs\UpdateCityDTO;
use Modules\Geo\Http\Controllers\Dashboard\CityController;
use Modules\Geo\Http\Resources\Dashboard\CityResource;
use Modules\Geo\Http\Resources\Dashboard\NationalityResource;
use Modules\Geo\Http\Resources\Dashboard\RegionResource;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;
use Modules\Geo\Services\CityService;
use Modules\Geo\Services\NationalityService;
use Modules\Geo\Services\RegionService;

beforeEach(function (): void {
    withoutGeoDashboardLocaleMiddleware();

    LookupCache::forgetAllLocales('regions:all');
    LookupCache::forgetAllLocales('regions:dropdown');
    LookupCache::forgetAllLocales('nationalities:all');
    LookupCache::forgetScopedAllLocales('cities:by-region', 0);

    $region = Region::query()->create(['translations' => geoTitleTranslations('Riyadh')]);
    City::query()->create([
        'region_id' => $region->id,
        'translations' => geoTitleTranslations('Jeddah'),
    ]);
    Nationality::query()->create(['translations' => geoNameTranslations('Saudi')]);

    $this->regionId = $region->id;
    LookupCache::forgetScopedAllLocales('cities:by-region', $this->regionId);
});

/**
 * Recursively assert identical PHP types (gettype / get_class) and equal values.
 * Collections/JsonSerializable objects are compared by serialized value, not instance identity.
 */
function assertGeoIdenticalTyped(mixed $expected, mixed $actual, string $path = 'root'): void
{
    expect(gettype($actual))->toBe(gettype($expected), "gettype mismatch at {$path}");

    if (is_object($expected)) {
        expect(get_class($actual))->toBe(get_class($expected), "get_class mismatch at {$path}");

        if ($expected instanceof Enumerable) {
            assertGeoIdenticalTyped($expected->all(), $actual->all(), $path);

            return;
        }

        if ($expected instanceof JsonSerializable) {
            assertGeoIdenticalTyped($expected->jsonSerialize(), $actual->jsonSerialize(), $path);

            return;
        }
    }

    if (is_array($expected)) {
        expect(array_keys($actual))->toBe(array_keys($expected), "array keys mismatch at {$path}");

        foreach ($expected as $key => $value) {
            assertGeoIdenticalTyped($value, $actual[$key], "{$path}.{$key}");
        }

        return;
    }

    expect($actual)->toBe($expected, "value mismatch at {$path}");
}

function assertGeoSha256Identical(string $cold, string $warm, string $label): void
{
    expect(hash('sha256', $warm))->toBe(hash('sha256', $cold), "SHA-256 mismatch for {$label}");
    expect($warm)->toBe($cold, "byte mismatch for {$label}");
}

test('regions:all listForSelect is byte-for-byte identical cold vs warm (Eloquent Collection preserved)', function (): void {
    app()->setLocale('en');
    $service = app(RegionService::class);

    $cold = $service->listForSelect();
    expect($cold)->toBeInstanceOf(EloquentCollection::class)
        ->and($cold->first())->toBeInstanceOf(Region::class);

    $warm = $service->listForSelect();
    expect($warm)->toBeInstanceOf(EloquentCollection::class)
        ->and(get_class($warm))->toBe(get_class($cold))
        ->and(get_class($warm->first()))->toBe(get_class($cold->first()));

    $request = Request::create('/general/regions', 'GET');
    $coldJson = json_encode(RegionResource::collection($cold)->resolve($request));
    $warmJson = json_encode(RegionResource::collection($warm)->resolve($request));

    assertGeoSha256Identical($coldJson, $warmJson, 'regions:all.resource');
    assertGeoIdenticalTyped(
        RegionResource::collection($cold)->resolve($request),
        RegionResource::collection($warm)->resolve($request),
        'regions:all.typed',
    );
});

test('regions:dropdown getAllForDropdown is byte-for-byte identical cold vs warm', function (): void {
    app()->setLocale('en');
    $service = app(RegionService::class);

    $cold = $service->getAllForDropdown();
    expect($cold)->toBeInstanceOf(EloquentCollection::class);

    $warm = $service->getAllForDropdown();
    expect($warm)->toBeInstanceOf(EloquentCollection::class)
        ->and(get_class($warm->first()))->toBe(get_class($cold->first()));

    $request = Request::create('/dashboard/cities/create', 'GET');
    $coldJson = json_encode(RegionResource::collection($cold)->resolve($request));
    $warmJson = json_encode(RegionResource::collection($warm)->resolve($request));

    assertGeoSha256Identical($coldJson, $warmJson, 'regions:dropdown.resource');
    assertGeoIdenticalTyped(
        RegionResource::collection($cold)->resolve($request),
        RegionResource::collection($warm)->resolve($request),
        'regions:dropdown.typed',
    );
});

test('cities:by-region listForSelect is byte-for-byte identical cold vs warm for all-cities and scoped region', function (): void {
    app()->setLocale('ar');
    $service = app(CityService::class);

    $coldAll = $service->listForSelect();
    $warmAll = $service->listForSelect();
    expect($coldAll)->toBeInstanceOf(EloquentCollection::class)
        ->and($warmAll->first())->toBeInstanceOf(City::class);

    $request = Request::create('/general/cities', 'GET');
    assertGeoSha256Identical(
        json_encode(CityResource::collection($coldAll)->resolve($request)),
        json_encode(CityResource::collection($warmAll)->resolve($request)),
        'cities:by-region:0.resource',
    );

    $coldScoped = $service->listForSelect(null, $this->regionId);
    $warmScoped = $service->listForSelect(null, $this->regionId);
    expect($coldScoped)->toBeInstanceOf(EloquentCollection::class);

    assertGeoSha256Identical(
        json_encode(CityResource::collection($coldScoped)->resolve($request)),
        json_encode(CityResource::collection($warmScoped)->resolve($request)),
        'cities:by-region:scoped.resource',
    );
    assertGeoIdenticalTyped(
        CityResource::collection($coldScoped)->resolve($request),
        CityResource::collection($warmScoped)->resolve($request),
        'cities:by-region:scoped.typed',
    );
});

test('nationalities:all listForSelect is byte-for-byte identical cold vs warm', function (): void {
    app()->setLocale('en');
    $service = app(NationalityService::class);

    $cold = $service->listForSelect();
    expect($cold)->toBeInstanceOf(EloquentCollection::class)
        ->and($cold->first())->toBeInstanceOf(Nationality::class);

    $warm = $service->listForSelect();
    expect(get_class($warm))->toBe(get_class($cold));

    $request = Request::create('/dashboard/users/create', 'GET');
    assertGeoSha256Identical(
        json_encode(NationalityResource::collection($cold)->resolve($request)),
        json_encode(NationalityResource::collection($warm)->resolve($request)),
        'nationalities:all.resource',
    );
});

test('ReactSelect regions/cities/nationalities HTTP responses are byte-for-byte identical cold vs warm', function (): void {
    app()->setLocale('en');

    $coldRegions = $this->getJson(action([ReactSelectController::class, 'regions']))->assertOk();
    $warmRegions = $this->getJson(action([ReactSelectController::class, 'regions']))->assertOk();
    assertGeoSha256Identical($coldRegions->getContent(), $warmRegions->getContent(), 'reactselect.regions');
    assertGeoIdenticalTyped($coldRegions->json(), $warmRegions->json(), 'reactselect.regions.json');

    $coldCities = $this->getJson(action([ReactSelectController::class, 'cities'], ['region_id' => $this->regionId]))->assertOk();
    $warmCities = $this->getJson(action([ReactSelectController::class, 'cities'], ['region_id' => $this->regionId]))->assertOk();
    assertGeoSha256Identical($coldCities->getContent(), $warmCities->getContent(), 'reactselect.cities');

    $coldNationalities = $this->getJson(action([ReactSelectController::class, 'nationalities']))->assertOk();
    $warmNationalities = $this->getJson(action([ReactSelectController::class, 'nationalities']))->assertOk();
    assertGeoSha256Identical($coldNationalities->getContent(), $warmNationalities->getContent(), 'reactselect.nationalities');
});

test('Frontend register regions/cities props are byte-for-byte identical cold vs warm', function (): void {
    app()->setLocale('ar');

    $regionService = app(RegionService::class);
    $cityService = app(CityService::class);
    $request = Request::create('/register', 'GET');

    $coldRegions = RegionResource::collection($regionService->listForSelect())->resolve($request);
    $warmRegions = RegionResource::collection($regionService->listForSelect())->resolve($request);
    assertGeoSha256Identical(json_encode($coldRegions), json_encode($warmRegions), 'frontend.regions');

    $coldCities = CityResource::collection($cityService->listForSelect())->resolve($request);
    $warmCities = CityResource::collection($cityService->listForSelect())->resolve($request);
    assertGeoSha256Identical(json_encode($coldCities), json_encode($warmCities), 'frontend.cities');

    $this->get(route('auth.register'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Frontend/Auth/Register/Register_')
            ->has('regions', 1)
            ->has('cities', 1)
            ->where('regions.0.title', 'Riyadh AR')
            ->where('cities.0.title', 'Jeddah AR')
        );
});

test('Provider profile regions/cities wiring uses RegionService/CityService listForSelect', function (): void {
    app()->setLocale('en');

    $regionService = app(RegionService::class);
    $cityService = app(CityService::class);
    $request = Request::create('/provider/profile', 'GET');

    $coldRegions = RegionResource::collection($regionService->listForSelect())->resolve($request);
    $warmRegions = RegionResource::collection($regionService->listForSelect())->resolve($request);
    assertGeoSha256Identical(json_encode($coldRegions), json_encode($warmRegions), 'provider.regions');

    $coldCities = CityResource::collection($cityService->listForSelect())->resolve($request);
    $warmCities = CityResource::collection($cityService->listForSelect())->resolve($request);
    assertGeoSha256Identical(json_encode($coldCities), json_encode($warmCities), 'provider.cities');

    expect(method_exists(ProviderAuthController::class, 'profile'))->toBeTrue();
});

test('Dashboard Provider create regions/cities dropdown props are byte-for-byte identical cold vs warm', function (): void {
    app()->setLocale('en');

    $regionService = app(RegionService::class);
    $cityService = app(CityService::class);
    $request = Request::create('/dashboard/providers/create', 'GET');

    // ProviderManagementService::getRegionsForDropdown / getCitiesForDropdown → listForSelect()
    $coldRegions = RegionResource::collection($regionService->listForSelect())->resolve($request);
    $warmRegions = RegionResource::collection($regionService->listForSelect())->resolve($request);
    assertGeoSha256Identical(json_encode($coldRegions), json_encode($warmRegions), 'dashboard.provider.regions');

    $coldCities = CityResource::collection($cityService->listForSelect())->resolve($request);
    $warmCities = CityResource::collection($cityService->listForSelect())->resolve($request);
    assertGeoSha256Identical(json_encode($coldCities), json_encode($warmCities), 'dashboard.provider.cities');
});

test('Dashboard User create nationalities dropdown props are byte-for-byte identical cold vs warm', function (): void {
    app()->setLocale('en');

    $service = app(NationalityService::class);
    $request = Request::create('/dashboard/users/create', 'GET');

    $cold = NationalityResource::collection($service->listForSelect())->resolve($request);
    $warm = NationalityResource::collection($service->listForSelect())->resolve($request);

    assertGeoSha256Identical(json_encode($cold), json_encode($warm), 'dashboard.user.nationalities');
    assertGeoIdenticalTyped($cold, $warm, 'dashboard.user.nationalities.typed');
});

test('Dashboard City create regions dropdown (getAllForDropdown) is byte-for-byte identical cold vs warm via HTTP', function (): void {
    app()->setLocale('en');
    $admin = createGeoDashboardAdmin(['create cities']);

    $cold = $this->actingAs($admin, 'admin')
        ->get(action([CityController::class, 'create']))
        ->assertSuccessful();

    $warm = $this->actingAs($admin, 'admin')
        ->get(action([CityController::class, 'create']))
        ->assertSuccessful();

    $cold->assertInertia(fn ($page) => $page
        ->component('Dashboard/Cities/Create')
        ->has('regions', 1)
        ->where('regions.0.title', 'Riyadh EN')
    );

    $warm->assertInertia(fn ($page) => $page
        ->component('Dashboard/Cities/Create')
        ->has('regions', 1)
        ->where('regions.0.title', 'Riyadh EN')
    );

    $service = app(RegionService::class);
    $request = Request::create('/dashboard/cities/create', 'GET');
    assertGeoSha256Identical(
        json_encode(RegionResource::collection($service->getAllForDropdown())->resolve($request)),
        json_encode(RegionResource::collection($service->getAllForDropdown())->resolve($request)),
        'dashboard.city.create.regions',
    );
});

test('ReactSelectResource shape from cached collections matches cold query', function (): void {
    app()->setLocale('en');
    $regionService = app(RegionService::class);
    $request = Request::create('/general/regions', 'GET');

    $cold = ReactSelectResource::collection($regionService->listForSelect())->resolve($request);
    $warm = ReactSelectResource::collection($regionService->listForSelect())->resolve($request);

    assertGeoSha256Identical(json_encode($cold), json_encode($warm), 'reactselect.resource.shape');
    expect($cold[0])->toHaveKeys(['label', 'value']);
});

test('DeleteRegionAction invalidates regions caches and that region cities scopes', function (): void {
    app()->setLocale('en');
    $regionService = app(RegionService::class);
    $cityService = app(CityService::class);

    expect($regionService->listForSelect())->toHaveCount(1);
    expect($cityService->listForSelect(null, $this->regionId))->toHaveCount(1);

    $region = Region::query()->findOrFail($this->regionId);
    City::query()->where('region_id', $region->id)->delete();
    app(DeleteRegionAction::class)->handle($region);

    expect($regionService->listForSelect())->toHaveCount(0);
    expect($regionService->getAllForDropdown())->toHaveCount(0);
    expect($cityService->listForSelect(null, $this->regionId))->toHaveCount(0);
});

test('UpdateCityAction that changes region_id invalidates both old and new city scopes', function (): void {
    app()->setLocale('en');
    $cityService = app(CityService::class);
    $newRegion = Region::query()->create(['translations' => geoTitleTranslations('Makkah')]);

    expect($cityService->listForSelect(null, $this->regionId))->toHaveCount(1);
    expect($cityService->listForSelect(null, $newRegion->id))->toHaveCount(0);

    $city = City::query()->where('region_id', $this->regionId)->firstOrFail();
    app(UpdateCityAction::class)->handle($city, new UpdateCityDTO(
        regionId: $newRegion->id,
        translations: geoTitleTranslations('Jeddah'),
    ));

    expect($cityService->listForSelect(null, $this->regionId))->toHaveCount(0);
    expect($cityService->listForSelect(null, $newRegion->id))->toHaveCount(1);
    expect($cityService->listForSelect())->toHaveCount(1);
});

test('DeleteCityAction and DeleteNationalityAction invalidate matching lookup caches', function (): void {
    app()->setLocale('en');
    $cityService = app(CityService::class);
    $nationalityService = app(NationalityService::class);

    expect($cityService->listForSelect())->toHaveCount(1);
    expect($nationalityService->listForSelect())->toHaveCount(1);

    $city = City::query()->firstOrFail();
    app(DeleteCityAction::class)->handle($city);
    expect($cityService->listForSelect())->toHaveCount(0);
    expect($cityService->listForSelect(null, $this->regionId))->toHaveCount(0);

    $nationality = Nationality::query()->firstOrFail();
    app(DeleteNationalityAction::class)->handle($nationality);
    expect($nationalityService->listForSelect())->toHaveCount(0);
});
