<?php

use Modules\Geo\Http\Controllers\Dashboard\CityController;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

test('admin can list cities with prams prop', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show cities']);
    City::factory()->count(2)->create();

    $this->actingAs($admin, 'admin')
        ->get(action([CityController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Cities/Index')
            ->has('prams')
            ->has('rows.data', 2)
        );
});

test('admin can store a city', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['create cities']);
    $region = Region::factory()->create();

    $this->actingAs($admin, 'admin')
        ->post(action([CityController::class, 'store']), [
            'region_id' => $region->id,
            'translations' => geoTitleTranslations('Jeddah'),
        ])
        ->assertRedirect(route('dashboard.cities.index'));

    expect(City::query()->whereTranslation('title', 'Jeddah EN')->exists())->toBeTrue();
});

test('admin can update and delete a city', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit cities', 'delete cities']);
    $city = City::factory()->create();

    $this->actingAs($admin, 'admin')
        ->put(action([CityController::class, 'update'], $city), [
            'region_id' => $city->region_id,
            'translations' => geoTitleTranslations('Updated City'),
        ])
        ->assertRedirect(route('dashboard.cities.index'));

    expect($city->fresh()->translate('en')->title)->toBe('Updated City EN');

    $this->actingAs($admin, 'admin')
        ->delete(action([CityController::class, 'destroy'], $city))
        ->assertRedirect(route('dashboard.cities.index'));

    expect(City::query()->whereKey($city->id)->exists())->toBeFalse();
});

test('admin can filter cities by region_id', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show cities']);
    $regionA = Region::factory()->create();
    $regionB = Region::factory()->create();
    City::factory()->create(['region_id' => $regionA->id]);
    City::factory()->create(['region_id' => $regionB->id]);

    $this->actingAs($admin, 'admin')
        ->get(action([CityController::class, 'index'], ['region_id' => $regionA->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('rows.data', 1));
});
