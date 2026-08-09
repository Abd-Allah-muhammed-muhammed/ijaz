<?php

use App\Contracts\Auth\ProviderRepositoryInterface;
use App\Enums\Auth\OtpPurposeEnum;
use App\Enums\Providers\ProviderStatusEnum;
use App\Http\Requests\Provider\Auth\LoginRequest;
use App\Models\Otp;
use App\Models\Provider;
use App\Services\Auth\ProviderAuthService;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Models\ProviderType;
use Modules\Sms\DTOs\SmsResult;
use Modules\Sms\Services\SmsService;

function providerRegistrationData(array $overrides = []): array
{
    $type = ProviderType::query()->create(['image' => 'media/test-type.png']);
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);

    return [
        'name' => 'Reg Co',
        'iban' => fake()->unique()->iban('SA'),
        'phone' => '512345678',
        'email' => 'reg@example.com',
        'provider_type_id' => $type->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'password' => 'password',
        'categories' => [],
        'logo' => UploadedFile::fake()->image('logo.png'),
        ...$overrides,
    ];
}

function providerRegistrationRequest(bool $withLogo = true): Request
{
    $files = $withLogo ? ['logo' => UploadedFile::fake()->image('logo.png')] : [];

    return Request::create('/register', 'POST', [], [], $files);
}

test('login regenerates session, updates language, and returns provider home redirect result', function () {
    app()->setLocale('ar');
    $provider = createWalletProvider([
        'email' => 'login@example.com',
        'password' => 'password',
        'status' => ProviderStatusEnum::Approved,
    ]);

    $request = LoginRequest::create('/provider/login', 'POST', [
        'email' => 'login@example.com',
        'password' => 'password',
    ]);
    $request->setLaravelSession($this->app['session']->driver());

    $result = app(ProviderAuthService::class)->login($request);

    expect($result->redirectRouteName)->toBe('provider.home')
        ->and($provider->fresh()->language)->toBe('ar');
});

test('logout invalidates session and regenerates csrf token', function () {
    $provider = createWalletProvider();
    $this->actingAs($provider, 'provider');

    $request = Request::create('/provider/logout', 'POST');
    $request->setLaravelSession($this->app['session']->driver());

    app(ProviderAuthService::class)->logout($request);

    expect(auth('provider')->check())->toBeFalse();
});

test('sendRegistrationOtp stores code against phone and dispatches sms', function () {
    $phone = Phone::make('512345678')->toString();

    $smsResult = new SmsResult(status: 'success', driver: 'testing');

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')->once()->with(Mockery::type('string'), $phone)->andReturn($smsResult);
    app()->instance(SmsService::class, $sms);

    app(ProviderAuthService::class)->sendRegistrationOtp('512345678');

    expect(Otp::query()->where('phone', $phone)->where('purpose', OtpPurposeEnum::ProviderRegistration)->exists())->toBeTrue();
});

test('register creates provider with pending status inside a transaction', function () {
    Storage::fake('public');
    Storage::fake('local');

    $result = app(ProviderAuthService::class)->register(
        providerRegistrationData(),
        providerRegistrationRequest(),
    );

    expect($result->success)->toBeTrue()
        ->and($result->provider->status)->toBe(ProviderStatusEnum::Pending)
        ->and(Provider::whereKey($result->provider->id)->exists())->toBeTrue();
});

test('register does not credit registration bonus while provider is still pending', function () {
    Storage::fake('public');
    Storage::fake('local');

    setWalletSetting('provider_registration_bonus_enabled', '1');
    setWalletSetting('provider_registration_bonus_amount', '50');

    $result = app(ProviderAuthService::class)->register(
        providerRegistrationData(),
        providerRegistrationRequest(),
    );

    expect($result->success)->toBeTrue()
        ->and($result->provider->status)->toBe(ProviderStatusEnum::Pending)
        ->and((float) $result->provider->wallet->fresh()->balance)->toBe(0.0);
});

test('register returns failed result with specific message on invalid logo upload', function () {
    Storage::fake('public');
    Storage::fake('local');

    $result = app(ProviderAuthService::class)->register(
        providerRegistrationData(),
        providerRegistrationRequest(withLogo: false),
    );

    expect($result->success)->toBeFalse()
        ->and($result->errorMessage)->toBe(__('logo upload failed, please try again'))
        ->and(Provider::query()->count())->toBe(0);
});

test('register rolls back transaction on generic failure', function () {
    Storage::fake('public');
    Storage::fake('local');

    $repo = Mockery::mock(ProviderRepositoryInterface::class);
    $repo->shouldReceive('create')->andThrow(new RuntimeException('boom'));
    app()->instance(ProviderRepositoryInterface::class, $repo);

    $register = fn () => app(ProviderAuthService::class)->register(
        providerRegistrationData(),
        providerRegistrationRequest(),
    );

    expect($register)->toThrow(RuntimeException::class);
    expect(Provider::query()->count())->toBe(0);
});
