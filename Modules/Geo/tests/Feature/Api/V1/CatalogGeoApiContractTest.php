<?php

use App\Enums\Providers\ProviderStatusEnum;
use App\Models\Provider;
use App\Support\Phone;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Models\ProviderType;

/**
 * Response-shape contract lock for Catalog Geo V1 APIs
 * (regions / cities / nationalities) after wiring through Geo Services.
 */
test('catalog regions response shape contract', function () {
    Region::query()->create(['translations' => geoTitleTranslations('Riyadh')]);

    $response = $this->getJson('/api/v1/catalog/regions');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys([
            'items', 'total', 'count', 'per_page', 'current_page', 'last_page', 'has_more_pages',
        ])
        ->and($json['data']['items'])->toBeArray()->not->toBeEmpty();

    expect($json['data']['items'][0])->toHaveKeys(['id', 'title']);
});

test('catalog regions filters by search', function () {
    Region::query()->create(['translations' => geoTitleTranslations('Riyadh')]);
    Region::query()->create(['translations' => geoTitleTranslations('Makkah')]);

    $response = $this->getJson('/api/v1/catalog/regions?search=Riyadh');
    $response->assertSuccessful();

    $titles = collect($response->json('data.items'))->pluck('title')->all();
    expect($titles)->toContain('Riyadh EN')
        ->and($titles)->not->toContain('Makkah EN');
});

test('catalog cities for region response shape contract', function () {
    $region = Region::query()->create(['translations' => geoTitleTranslations('Region A')]);
    City::query()->create([
        'region_id' => $region->id,
        'translations' => geoTitleTranslations('Jeddah'),
    ]);
    City::query()->create([
        'region_id' => Region::query()->create(['translations' => geoTitleTranslations('Other')])->id,
        'translations' => geoTitleTranslations('Dammam'),
    ]);

    $response = $this->getJson("/api/v1/catalog/regions/{$region->id}/cities");
    $response->assertSuccessful();

    $titles = collect($response->json('data.items'))->pluck('title')->all();
    expect($response->json('data'))->toHaveKeys([
        'items', 'total', 'count', 'per_page', 'current_page', 'last_page', 'has_more_pages',
    ])
        ->and($titles)->toContain('Jeddah EN')
        ->and($titles)->not->toContain('Dammam EN');
});

test('catalog nationalities response shape contract', function () {
    Nationality::query()->create(['translations' => geoNameTranslations('Saudi')]);

    $response = $this->getJson('/api/v1/catalog/nationalities');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json['data'])->toHaveKeys([
        'items', 'total', 'count', 'per_page', 'current_page', 'last_page', 'has_more_pages',
    ])
        ->and($json['data']['items'][0])->toHaveKeys(['id', 'name']);
});

test('catalog providers looks up by phone once and returns provider resource', function () {
    $providerType = ProviderType::query()->create([
        'image' => 'provider-types/test.png',
        'translations' => [
            'en' => ['name' => 'Individual EN', 'description' => 'Desc EN'],
            'ar' => ['name' => 'Individual AR', 'description' => 'Desc AR'],
            'ur' => ['name' => 'Individual UR', 'description' => 'Desc UR'],
            'hi' => ['name' => 'Individual HI', 'description' => 'Desc HI'],
        ],
    ]);

    $rawPhone = '0501234567';
    $normalizedPhone = Phone::make($rawPhone)->toString();

    $provider = Provider::query()->create([
        'name' => 'Catalog Provider',
        'iban' => 'SA'.fake()->unique()->numerify('################'),
        'logo' => 'providers/test.png',
        'password' => bcrypt('password'),
        'phone' => $normalizedPhone,
        'provider_type_id' => $providerType->id,
        'status' => ProviderStatusEnum::Approved,
    ]);

    $response = $this->getJson('/api/v1/catalog/providers?phone='.$rawPhone);
    $response->assertSuccessful()
        ->assertJsonPath('data.id', $provider->id)
        ->assertJsonPath('data.name', 'Catalog Provider')
        ->assertJsonPath('data.phone', $normalizedPhone);
});

test('catalog providers returns not found for unknown phone', function () {
    $this->getJson('/api/v1/catalog/providers?phone=0509999999')
        ->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'not found');
});
