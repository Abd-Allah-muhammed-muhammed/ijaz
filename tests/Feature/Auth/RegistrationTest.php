<?php

use App\Enums\Providers\ProviderStatusEnum;
use App\Models\Category;
use App\Models\City;
use App\Models\Provider;
use App\Models\ProviderType;
use App\Models\Region;
use App\Models\RegisterVerificationCode;
use App\Services\Sms\Phone;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Sms\DTOs\SmsResult;
use Modules\Sms\Services\SmsService;
use Modules\Wallet\Models\WalletTransaction;

beforeEach(function () {
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();

    Storage::fake('public');
    Storage::fake('local');
});

/**
 * @return array{type: ProviderType, region: Region, city: City, category: Category}
 */
function registrationFixtures(): array
{
    $type = ProviderType::query()->create([
        'image' => 'media/test-type.png',
        'files' => [
            'id_image' => false,
            'commercial_record' => false,
            'freelancer_certification' => false,
            'iban_certification' => false,
            'license_to_practice_law' => false,
        ],
    ]);
    $type->translations()->create([
        'locale' => 'en',
        'name' => 'General Provider',
    ]);

    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $category = Category::factory()->create();

    return compact('type', 'region', 'city', 'category');
}

function seedRegistrationOtp(string $rawPhone, string $token = '1234', ?CarbonInterface $expiresAt = null): RegisterVerificationCode
{
    $phone = Phone::make($rawPhone)->toString();

    return RegisterVerificationCode::query()->updateOrCreate(
        ['queryable' => $phone],
        [
            'token' => $token,
            'expires_at' => $expiresAt ?? now()->addMinutes(5),
        ],
    );
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validRegistrationPayload(array $overrides = []): array
{
    $fixtures = registrationFixtures();
    $phone = $overrides['phone'] ?? '512345678';
    $seedOtp = $overrides['seed_otp'] ?? true;
    $otpExpiresAt = $overrides['otp_expires_at'] ?? null;
    unset($overrides['seed_otp'], $overrides['otp_expires_at']);

    if ($seedOtp) {
        seedRegistrationOtp($phone, '1234', $otpExpiresAt);
    }

    return [
        'name' => 'Acme Services',
        'provider_type_id' => $fixtures['type']->id,
        'region_id' => $fixtures['region']->id,
        'city_id' => $fixtures['city']->id,
        'address' => '123 King Fahd Road, Riyadh',
        'phone' => $phone,
        'email' => 'provider@example.com',
        'iban' => 'SA0380000000608010167519',
        'about' => 'We provide professional services across the kingdom.',
        'logo' => UploadedFile::fake()->image('logo.png'),
        'password' => 'password',
        'password_confirmation' => 'password',
        'categories' => [
            ['id' => $fixtures['category']->id],
        ],
        'otp' => '1234',
        ...$overrides,
        '_category' => $fixtures['category'],
    ];
}

test('registration page can be rendered', function () {
    $this->get(route('auth.register'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Frontend/Auth/Register_')
            ->has('types')
            ->has('regions')
            ->has('cities')
        );
});

test('legacy provider registration page redirects to canonical registration page', function () {
    $this->get(route('provider.register'))
        ->assertRedirect(route('auth.register'));
});

test('provider registration does not expose a separate otp verification route', function () {
    expect(Route::has('auth.register.otp.verify'))->toBeFalse();
});

test('provider can send otp before registering', function () {
    $phone = Phone::make('512345678')->toString();
    RateLimiter::clear('otp-send:'.$phone);

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')
        ->once()
        ->with(Mockery::type('string'), $phone)
        ->andReturn(new SmsResult(status: 'success', driver: 'testing'));
    app()->instance(SmsService::class, $sms);

    $this->postJson(route('auth.register.otp'), [
        'phone' => '512345678',
    ])->assertSuccessful()
        ->assertExactJson([]);

    expect(RegisterVerificationCode::query()->where('queryable', $phone)->exists())->toBeTrue();

    RateLimiter::clear('otp-send:'.$phone);
});

test('provider can register with valid otp and required fields', function () {
    $payload = validRegistrationPayload();
    unset($payload['_category']);

    $this->from(route('auth.register'))
        ->post(route('auth.register.submit'), $payload)
        ->assertRedirect(route('auth.register'))
        ->assertSessionHas('success', __('data saved successfully'))
        ->assertSessionHas('id');

    $provider = Provider::query()->where('email', 'provider@example.com')->first();

    expect($provider)->not->toBeNull()
        ->and(session('id'))->toBe($provider->id);
});

test('registration fails with invalid otp', function () {
    $payload = validRegistrationPayload(['otp' => '9999']);
    unset($payload['_category']);

    $this->from(route('auth.register'))
        ->post(route('auth.register.submit'), $payload)
        ->assertRedirect(route('auth.register'))
        ->assertSessionHasErrors('otp');

    expect(Provider::query()->count())->toBe(0);
});

test('registration fails with expired otp', function () {
    $payload = validRegistrationPayload([
        'otp_expires_at' => now()->subMinute(),
    ]);
    unset($payload['_category']);

    $this->from(route('auth.register'))
        ->post(route('auth.register.submit'), $payload)
        ->assertRedirect(route('auth.register'))
        ->assertSessionHasErrors('otp');

    expect(Provider::query()->count())->toBe(0);
});

test('newly registered provider has pending status', function () {
    $payload = validRegistrationPayload();
    unset($payload['_category']);

    $this->from(route('auth.register'))
        ->post(route('auth.register.submit'), $payload)
        ->assertRedirect(route('auth.register'));

    $provider = Provider::query()->where('email', 'provider@example.com')->first();

    expect($provider)->not->toBeNull()
        ->and($provider->status)->toBe(ProviderStatusEnum::Pending);
});

test('newly registered provider receives registration bonus when enabled', function () {
    setWalletSetting('provider_registration_bonus_enabled', '1');
    setWalletSetting('provider_registration_bonus_amount', '50');

    $payload = validRegistrationPayload();
    unset($payload['_category']);

    $this->from(route('auth.register'))
        ->post(route('auth.register.submit'), $payload)
        ->assertRedirect(route('auth.register'));

    $provider = Provider::query()->where('email', 'provider@example.com')->first();

    expect($provider)->not->toBeNull()
        ->and((float) $provider->wallet->fresh()->balance)->toBe(50.0)
        ->and(
            WalletTransaction::query()
                ->where('wallet_id', $provider->wallet->id)
                ->where('description', __('Registration bonus'))
                ->exists()
        )->toBeTrue();
});

test('newly registered provider does not receive bonus when setting disabled', function () {
    setWalletSetting('provider_registration_bonus_enabled', '0');
    setWalletSetting('provider_registration_bonus_amount', '50');

    $payload = validRegistrationPayload();
    unset($payload['_category']);

    $this->from(route('auth.register'))
        ->post(route('auth.register.submit'), $payload)
        ->assertRedirect(route('auth.register'));

    $provider = Provider::query()->where('email', 'provider@example.com')->first();

    expect($provider)->not->toBeNull()
        ->and((float) $provider->wallet->fresh()->balance)->toBe(0.0)
        ->and(WalletTransaction::query()->where('wallet_id', $provider->wallet->id)->count())->toBe(0);
});

test('registration fails and rolls back when logo upload is invalid', function () {
    $payload = validRegistrationPayload();
    unset($payload['_category'], $payload['logo']);

    $this->from(route('auth.register'))
        ->post(route('auth.register.submit'), $payload)
        ->assertRedirect(route('auth.register'))
        ->assertSessionHasErrors('logo');

    expect(Provider::query()->count())->toBe(0);
});

test('registration generates a provider code after creation', function () {
    $payload = validRegistrationPayload();
    unset($payload['_category']);

    $this->from(route('auth.register'))
        ->post(route('auth.register.submit'), $payload)
        ->assertRedirect(route('auth.register'));

    $provider = Provider::query()->where('email', 'provider@example.com')->first();

    expect($provider)->not->toBeNull()
        ->and($provider->code)->toBe(date('dmy').$provider->id);
});

test('registration syncs selected categories', function () {
    $payload = validRegistrationPayload();
    $category = $payload['_category'];
    unset($payload['_category']);

    $this->from(route('auth.register'))
        ->post(route('auth.register.submit'), $payload)
        ->assertRedirect(route('auth.register'));

    $provider = Provider::query()->where('email', 'provider@example.com')->first();

    expect($provider)->not->toBeNull()
        ->and($provider->categories()->pluck('categories.id')->all())->toBe([$category->id]);
});
