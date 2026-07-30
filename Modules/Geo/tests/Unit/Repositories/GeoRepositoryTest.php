<?php

use App\Models\User;
use App\Support\Normalize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

test('searching regions by partial title returns matching results', function () {
    app()->setLocale('en');

    $matching = app(RegionRepositoryInterface::class)->create([
        'en' => ['title' => 'Northern Frontier'],
        'ar' => ['title' => 'الحدود الشمالية'],
    ]);
    app(RegionRepositoryInterface::class)->create([
        'en' => ['title' => 'Eastern Province'],
        'ar' => ['title' => 'المنطقة الشرقية'],
    ]);

    $results = app(RegionRepositoryInterface::class)->paginate(
        Request::create('/', 'GET', ['search' => 'no'])
    );

    expect($results->total())->toBeGreaterThan(0)
        ->and($results->pluck('id'))->toContain($matching->id);
});

test('searching cities by partial title returns matching results', function () {
    app()->setLocale('en');

    $region = Region::factory()->create();
    $matching = app(CityRepositoryInterface::class)->create($region->id, [
        'en' => ['title' => 'Northern City'],
        'ar' => ['title' => 'مدينة شمالية'],
    ]);
    app(CityRepositoryInterface::class)->create($region->id, [
        'en' => ['title' => 'Southern City'],
        'ar' => ['title' => 'مدينة جنوبية'],
    ]);

    $results = app(CityRepositoryInterface::class)->paginate(
        Request::create('/', 'GET', ['search' => 'no'])
    );

    expect($results->total())->toBeGreaterThan(0)
        ->and($results->pluck('id'))->toContain($matching->id);
});

test('region search finds rows after normalized_title backfill', function () {
    app()->setLocale('en');

    $regionId = DB::table('regions')->insertGetId([
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('region_translations')->insert([
        'region_id' => $regionId,
        'locale' => 'en',
        'title' => 'BackfillNorthernRegion',
        'normalized_title' => null,
    ]);

    expect(
        DB::selectOne(
            'SELECT normalized_title FROM region_translations WHERE region_id = ? AND locale = ?',
            [$regionId, 'en']
        )->normalized_title
    )->toBeNull();

    DB::table('region_translations')
        ->whereNull('normalized_title')
        ->orWhere('normalized_title', '')
        ->orderBy('id')
        ->each(function (object $row): void {
            DB::table('region_translations')
                ->where('id', $row->id)
                ->update([
                    'normalized_title' => Normalize::make($row->title, $row->locale)->toString(),
                ]);
        });

    $results = app(RegionRepositoryInterface::class)->paginate(
        Request::create('/', 'GET', ['search' => 'northern'])
    );

    expect($results->pluck('id'))->toContain($regionId);
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
