<?php

use App\Contracts\Auth\ProviderRepositoryInterface;
use App\Enums\Providers\ProviderStatusEnum;
use App\Models\City;
use App\Models\Provider;
use App\Models\ProviderType;
use App\Models\Region;

test('findAuthenticated returns the currently authenticated provider', function () {
    $provider = createWalletProvider();
    $this->actingAs($provider, 'provider');

    $found = app(ProviderRepositoryInterface::class)->findAuthenticated();

    expect($found)->not->toBeNull()
        ->and($found->is($provider))->toBeTrue();
});

test('findAuthenticated returns null when no provider is authenticated', function () {
    expect(app(ProviderRepositoryInterface::class)->findAuthenticated())->toBeNull();
});

test('create persists and returns a new provider', function () {
    $type = ProviderType::query()->create(['image' => 'media/test-type.png']);
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);

    $provider = app(ProviderRepositoryInterface::class)->create([
        'name' => 'Repo Co',
        'iban' => fake()->unique()->iban('SA'),
        'logo' => 'media/test-logo.png',
        'provider_type_id' => $type->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'password' => 'password',
        'status' => ProviderStatusEnum::Pending,
        'language' => 'en',
    ]);

    expect($provider->exists)->toBeTrue()
        ->and(Provider::whereKey($provider->getKey())->exists())->toBeTrue();
});
