<?php

use App\Enums\OperationStatusEnum;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Payment\Enums\PaymentStatusEnum;
use App\Enums\Providers\ProviderStatusEnum;
use App\Models\Admin;
use App\Models\City;
use App\Models\Provider;
use App\Models\ProviderType;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;

function createWalletUser(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function createWalletProvider(array $attributes = []): Provider
{
    $providerType = ProviderType::query()->create(['image' => 'media/test-type.png']);
    $providerType->translations()->create([
        'locale' => 'en',
        'name' => 'Test Provider Type',
    ]);
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);

    return Provider::query()->create([
        'name' => fake()->company(),
        'iban' => fake()->unique()->iban('SA'),
        'logo' => 'media/test-logo.png',
        'provider_type_id' => $providerType->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'password' => 'password',
        'status' => ProviderStatusEnum::Approved,
        'language' => 'en',
        ...$attributes,
    ]);
}

function createWalletAdmin(): Admin
{
    return Admin::query()->create([
        'name' => 'Wallet Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
}

function createTopUpFor(Model $owner, array $attributes = []): TopUpRequest
{
    return TopUpRequest::query()->create([
        'user_id' => $owner->getKey(),
        'user_type' => $owner::class,
        'wallet_id' => $owner->wallet->id,
        'amount' => 100,
        'status' => OperationStatusEnum::Pending->value,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'payment_status' => PaymentStatusEnum::Pending->value,
        ...$attributes,
    ]);
}

function createWithdrawFor(Model $owner, array $attributes = []): WithdrawRequest
{
    return WithdrawRequest::query()->create([
        'user_id' => $owner->getKey(),
        'user_type' => $owner::class,
        'wallet_id' => $owner->wallet->id,
        'amount' => 50,
        'status' => OperationStatusEnum::Pending->value,
        ...$attributes,
    ]);
}

function withoutWalletLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

function fundWallet(User|Provider $owner, float $amount): void
{
    DB::transaction(fn () => app(WalletService::class)->credit(
        $owner,
        $amount,
        $owner,
        'Test wallet funding',
    ));
}
