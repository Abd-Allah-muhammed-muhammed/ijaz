<?php

use Database\Seeders\RegionsAndCitiesSeeder;
use Modules\Geo\Models\Region;

test('RegionsAndCitiesSeeder skips a region when a region with that exact Arabic title already exists', function () {
    Region::query()->create([
        'translations' => [
            'en' => ['title' => 'Riyadh Existing'],
            'ar' => ['title' => 'الرياض'],
        ],
    ]);

    $this->seed(RegionsAndCitiesSeeder::class);

    expect(Region::query()->whereTranslation('title', 'الرياض', 'ar')->count())->toBe(1)
        ->and(Region::query()->count())->toBe(30);
});

test('RegionsAndCitiesSeeder is idempotent for regions — a second run does not create another duplicate set', function () {
    $this->seed(RegionsAndCitiesSeeder::class);
    $afterFirst = Region::query()->count();

    $this->seed(RegionsAndCitiesSeeder::class);

    expect($afterFirst)->toBe(30)
        ->and(Region::query()->count())->toBe($afterFirst);
});
