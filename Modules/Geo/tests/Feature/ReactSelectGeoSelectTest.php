<?php

use App\Http\Controllers\General\ReactSelectController;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;

/**
 * Regression lock for the Region/City/Nationality select extraction
 * from ReactSelectController into Geo Actions/Services/Repositories.
 */
beforeEach(function (): void {
    withoutGeoDashboardLocaleMiddleware();
});

it('returns regions as react-select options', function (): void {
    $riyadh = Region::query()->create(['translations' => geoTitleTranslations('Riyadh')]);
    Region::query()->create(['translations' => geoTitleTranslations('Makkah')]);

    $this->getJson(action([ReactSelectController::class, 'regions']))
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['label', 'value'],
            ],
        ])
        ->assertJsonFragment(['label' => 'Riyadh EN', 'value' => (string) $riyadh->id])
        ->assertJsonFragment(['label' => 'Makkah EN']);
});

it('filters regions by search', function (): void {
    Region::query()->create(['translations' => geoTitleTranslations('Riyadh')]);
    Region::query()->create(['translations' => geoTitleTranslations('Makkah')]);

    $this->getJson(action([ReactSelectController::class, 'regions'], ['search' => 'Riyadh']))
        ->assertOk()
        ->assertJsonFragment(['label' => 'Riyadh EN'])
        ->assertJsonMissing(['label' => 'Makkah EN']);
});

it('filters cities by region_id', function (): void {
    $regionA = Region::query()->create(['translations' => geoTitleTranslations('Region A')]);
    $regionB = Region::query()->create(['translations' => geoTitleTranslations('Region B')]);

    $jeddah = City::query()->create([
        'region_id' => $regionA->id,
        'translations' => geoTitleTranslations('Jeddah'),
    ]);

    City::query()->create([
        'region_id' => $regionB->id,
        'translations' => geoTitleTranslations('Dammam'),
    ]);

    $this->getJson(action([ReactSelectController::class, 'cities'], ['region_id' => $regionA->id]))
        ->assertOk()
        ->assertJsonFragment(['label' => 'Jeddah EN', 'value' => (string) $jeddah->id])
        ->assertJsonMissing(['label' => 'Dammam EN']);
});

it('returns every city when no region_id is given', function (): void {
    City::factory()->count(2)->create();

    $response = $this->getJson(action([ReactSelectController::class, 'cities']))->assertOk();

    expect($response->json('data'))->toHaveCount(2);
});

it('filters cities by search within a region', function (): void {
    $region = Region::query()->create(['translations' => geoTitleTranslations('Region A')]);

    City::query()->create([
        'region_id' => $region->id,
        'translations' => geoTitleTranslations('Jeddah'),
    ]);

    City::query()->create([
        'region_id' => $region->id,
        'translations' => geoTitleTranslations('Dammam'),
    ]);

    $this->getJson(action(
        [ReactSelectController::class, 'cities'],
        ['region_id' => $region->id, 'search' => 'Jeddah'],
    ))
        ->assertOk()
        ->assertJsonFragment(['label' => 'Jeddah EN'])
        ->assertJsonMissing(['label' => 'Dammam EN']);
});

it('returns nationalities as react-select options filtered by name', function (): void {
    $saudi = Nationality::query()->create(['translations' => geoNameTranslations('Saudi')]);
    Nationality::query()->create(['translations' => geoNameTranslations('Egyptian')]);

    $this->getJson(action([ReactSelectController::class, 'nationalities'], ['search' => 'Saudi']))
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['label', 'value'],
            ],
        ])
        ->assertJsonFragment(['label' => 'Saudi EN', 'value' => (string) $saudi->id])
        ->assertJsonMissing(['label' => 'Egyptian EN']);
});
