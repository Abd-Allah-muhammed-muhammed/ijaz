<?php

use App\Enums\Providers\ProviderStatusEnum;
use App\Models\Provider;
use Modules\Geo\Actions\Region\CleanupDuplicateRegionsAction;
use Modules\Geo\Http\Controllers\Dashboard\RegionController;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Models\ProviderType;
use Modules\Opportunity\Models\Opportunity;

const GEO_DUPLICATE_REGION_IDS = [14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26];

const GEO_OFFICIAL_REGION_IDS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];

const GEO_EXTRA_REGION_IDS = [27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43];

/**
 * @return array{
 *     officialCity: City,
 *     orphanCityIds: list<int>,
 *     extraCity: City,
 *     provider: Provider,
 *     opportunity: Opportunity
 * }
 */
function seedGeoDuplicateSeederRegionFixture(): array
{
    foreach (GEO_OFFICIAL_REGION_IDS as $id) {
        createGeoRegionWithExplicitId($id, "Official Region {$id}", "منطقة رسمية {$id}");
    }

    foreach (GEO_DUPLICATE_REGION_IDS as $id) {
        $officialId = $id - 13;
        createGeoRegionWithExplicitId($id, "Duplicate Region {$id}", "منطقة رسمية {$officialId}");
    }

    foreach (GEO_EXTRA_REGION_IDS as $id) {
        createGeoRegionWithExplicitId($id, "Extra Region {$id}", "منطقة إضافية {$id}");
    }

    $officialCity = City::query()->create([
        'region_id' => 1,
        'translations' => [
            'en' => ['title' => 'Official City 1'],
            'ar' => ['title' => 'مدينة رسمية 1'],
        ],
    ]);

    $orphanCityIds = [];
    $duplicateCount = count(GEO_DUPLICATE_REGION_IDS);
    for ($n = 1; $n <= 27; $n++) {
        $regionId = GEO_DUPLICATE_REGION_IDS[($n - 1) % $duplicateCount];

        $city = City::query()->create([
            'region_id' => $regionId,
            'translations' => [
                'en' => ['title' => "Orphan City {$n}"],
                'ar' => ['title' => "مدينة يتيمة {$n}"],
            ],
        ]);
        $orphanCityIds[] = $city->id;
    }

    $extraCity = City::query()->create([
        'region_id' => 27,
        'translations' => [
            'en' => ['title' => 'Extra City 27'],
            'ar' => ['title' => 'مدينة إضافية 27'],
        ],
    ]);

    $providerType = ProviderType::query()->create(['image' => 'media/test-type.png']);
    $providerType->translations()->create([
        'locale' => 'en',
        'name' => 'Test Provider Type',
    ]);

    $provider = Provider::query()->create([
        'name' => 'Official Region Provider',
        'iban' => fake()->unique()->iban('SA'),
        'logo' => 'media/test-logo.png',
        'provider_type_id' => $providerType->id,
        'region_id' => 1,
        'city_id' => $officialCity->id,
        'password' => 'password',
        'status' => ProviderStatusEnum::Approved,
        'language' => 'en',
    ]);

    $opportunity = Opportunity::factory()->create([
        'region_id' => 1,
        'city_id' => $officialCity->id,
    ]);

    return [
        'officialCity' => $officialCity,
        'orphanCityIds' => $orphanCityIds,
        'extraCity' => $extraCity,
        'provider' => $provider,
        'opportunity' => $opportunity,
    ];
}

function createGeoRegionWithExplicitId(int $id, string $englishTitle, string $arabicTitle): Region
{
    $region = Region::query()->forceCreate(['id' => $id]);

    $region->translations()->create(['locale' => 'en', 'title' => $englishTitle]);
    $region->translations()->create(['locale' => 'ar', 'title' => $arabicTitle]);
    $region->translations()->create(['locale' => 'hi', 'title' => "{$englishTitle} HI"]);
    $region->translations()->create(['locale' => 'ur', 'title' => "{$englishTitle} UR"]);

    return $region;
}

test('after cleanup, editing region 1 (or any of 1-13) without changing its Arabic title no longer fails unique validation', function () {
    seedGeoDuplicateSeederRegionFixture();

    $this->artisan('geo:cleanup-duplicate-regions')->assertSuccessful();

    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit regions']);
    $region = Region::query()->with('translations')->findOrFail(1);

    $translations = [];
    foreach (['en', 'ar', 'hi', 'ur'] as $locale) {
        $translations[$locale] = ['title' => $region->translate($locale)->title];
    }

    $this->actingAs($admin, 'admin')
        ->put(action([RegionController::class, 'update'], $region), [
            'translations' => $translations,
        ])
        ->assertRedirect(route('dashboard.regions.index'))
        ->assertSessionHasNoErrors();

    expect($region->fresh()->translate('ar')->title)->toBe('منطقة رسمية 1');
});

test('regions 14-26 no longer exist after the cleanup command runs', function () {
    seedGeoDuplicateSeederRegionFixture();

    $this->artisan('geo:cleanup-duplicate-regions')->assertSuccessful();

    expect(Region::query()->whereIn('id', GEO_DUPLICATE_REGION_IDS)->count())->toBe(0);
});

test('the 27 cities that belonged to regions 14-26 are also gone (cascade delete)', function () {
    $fixture = seedGeoDuplicateSeederRegionFixture();

    expect(City::query()->whereIn('region_id', GEO_DUPLICATE_REGION_IDS)->count())->toBe(27);

    $this->artisan('geo:cleanup-duplicate-regions')->assertSuccessful();

    expect(City::query()->whereIn('region_id', GEO_DUPLICATE_REGION_IDS)->count())->toBe(0)
        ->and(City::query()->whereIn('id', $fixture['orphanCityIds'])->count())->toBe(0);
});

test('regions 27-43 are completely untouched by the cleanup', function () {
    $fixture = seedGeoDuplicateSeederRegionFixture();

    $titlesBefore = Region::query()
        ->whereIn('id', GEO_EXTRA_REGION_IDS)
        ->with('translations')
        ->get()
        ->mapWithKeys(fn (Region $region): array => [$region->id => $region->translate('ar')->title]);

    $this->artisan('geo:cleanup-duplicate-regions')->assertSuccessful();

    $titlesAfter = Region::query()
        ->whereIn('id', GEO_EXTRA_REGION_IDS)
        ->with('translations')
        ->get()
        ->mapWithKeys(fn (Region $region): array => [$region->id => $region->translate('ar')->title]);

    expect(Region::query()->whereIn('id', GEO_EXTRA_REGION_IDS)->count())->toBe(17)
        ->and($titlesAfter->all())->toBe($titlesBefore->all())
        ->and(City::query()->whereKey($fixture['extraCity']->id)->exists())->toBeTrue()
        ->and($fixture['extraCity']->fresh()->region_id)->toBe(27);
});

test('regions 1-13 and all their real dependent records (cities, providers, opportunities) are completely unaffected', function () {
    $fixture = seedGeoDuplicateSeederRegionFixture();

    $titlesBefore = Region::query()
        ->whereIn('id', GEO_OFFICIAL_REGION_IDS)
        ->with('translations')
        ->get()
        ->mapWithKeys(fn (Region $region): array => [$region->id => $region->translate('ar')->title]);

    $this->artisan('geo:cleanup-duplicate-regions')->assertSuccessful();

    $titlesAfter = Region::query()
        ->whereIn('id', GEO_OFFICIAL_REGION_IDS)
        ->with('translations')
        ->get()
        ->mapWithKeys(fn (Region $region): array => [$region->id => $region->translate('ar')->title]);

    expect(Region::query()->whereIn('id', GEO_OFFICIAL_REGION_IDS)->count())->toBe(13)
        ->and($titlesAfter->all())->toBe($titlesBefore->all())
        ->and(City::query()->whereKey($fixture['officialCity']->id)->exists())->toBeTrue()
        ->and($fixture['officialCity']->fresh()->region_id)->toBe(1)
        ->and($fixture['provider']->fresh()->region_id)->toBe(1)
        ->and($fixture['provider']->fresh()->city_id)->toBe($fixture['officialCity']->id)
        ->and($fixture['opportunity']->fresh()->region_id)->toBe(1)
        ->and($fixture['opportunity']->fresh()->city_id)->toBe($fixture['officialCity']->id);
});

test('running the cleanup command twice is safe (idempotent) — second run is a no-op since 14-26 no longer exist', function () {
    seedGeoDuplicateSeederRegionFixture();

    $this->artisan('geo:cleanup-duplicate-regions')->assertSuccessful();

    $this->artisan('geo:cleanup-duplicate-regions')
        ->expectsOutputToContain('Nothing to delete')
        ->assertSuccessful();

    expect(Region::query()->whereIn('id', GEO_DUPLICATE_REGION_IDS)->count())->toBe(0)
        ->and(Region::query()->whereIn('id', GEO_OFFICIAL_REGION_IDS)->count())->toBe(13)
        ->and(Region::query()->whereIn('id', GEO_EXTRA_REGION_IDS)->count())->toBe(17);
});

test('dry-run reports region and city counts without deleting anything', function () {
    seedGeoDuplicateSeederRegionFixture();

    $this->artisan('geo:cleanup-duplicate-regions', ['--dry-run' => true])
        ->expectsOutputToContain('Would delete 13 region(s) and 27 city/cities.')
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    expect(Region::query()->whereIn('id', GEO_DUPLICATE_REGION_IDS)->count())->toBe(13)
        ->and(City::query()->whereIn('region_id', GEO_DUPLICATE_REGION_IDS)->count())->toBe(27)
        ->and(CleanupDuplicateRegionsAction::DUPLICATE_REGION_IDS)->toBe(GEO_DUPLICATE_REGION_IDS);
});
