<?php

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Geo\Contracts\Repositories\CityRepositoryInterface;
use Modules\Geo\Contracts\Repositories\NationalityRepositoryInterface;
use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\Exceptions\GeoException;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;

test('region repository paginates with search', function () {
    $matching = app(RegionRepositoryInterface::class)->create(geoTitleTranslations('SearchableAlpha'));
    app(RegionRepositoryInterface::class)->create(geoTitleTranslations('OtherBeta'));

    $request = Request::create('/', 'GET', ['search' => 'SearchableAlpha']);
    $results = app(RegionRepositoryInterface::class)->paginate($request);

    expect($results->total())->toBe(1)
        ->and($results->first()->is($matching))->toBeTrue();
});

test('city repository filters by region_id', function () {
    $regionA = Region::factory()->create();
    $regionB = Region::factory()->create();
    City::factory()->create(['region_id' => $regionA->id]);
    City::factory()->create(['region_id' => $regionB->id]);

    $request = Request::create('/', 'GET', ['region_id' => $regionA->id]);
    $results = app(CityRepositoryInterface::class)->paginate($request);

    expect($results->total())->toBe(1);
});

test('region repository getAllForDropdown returns translated regions', function () {
    Region::factory()->count(2)->create();

    $regions = app(RegionRepositoryInterface::class)->getAllForDropdown();

    expect($regions)->toHaveCount(2)
        ->and($regions->first()->relationLoaded('translation'))->toBeTrue();
});

test('nationality repository delete throws when users exist', function () {
    $nationality = Nationality::query()->create(['translations' => geoNameTranslations('Repo Guard')]);
    User::factory()->create(['nationality_id' => $nationality->id]);

    $repository = app(NationalityRepositoryInterface::class);

    expect(fn () => $repository->delete($nationality))
        ->toThrow(GeoException::class);

    expect(Nationality::query()->whereKey($nationality->id)->exists())->toBeTrue();
});
