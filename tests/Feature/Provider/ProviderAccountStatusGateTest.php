<?php

use App\Actions\Auth\Provider\GenerateProviderAccountStatusGateUrlAction;
use App\Enums\Providers\ProviderStatusEnum;
use App\Http\Controllers\Provider\HomeController;
use App\Models\Provider;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
});

function createGateProvider(ProviderStatusEnum $status, array $attributes = []): Provider
{
    $provider = createWalletProvider([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'status' => $status,
        ...$attributes,
    ]);

    if ($status === ProviderStatusEnum::Blocked) {
        $provider->block(5, $attributes['block_reason'] ?? 'blocked for testing');
    }

    return $provider->fresh();
}

function assertRedirectsToSignedAccountStatusGate($response, Provider $provider): void
{
    $response->assertRedirect();

    $location = (string) $response->headers->get('Location');

    expect($location)
        ->toContain('/provider/account-status/'.$provider->id)
        ->toContain('signature=')
        ->toContain('expires=');
}

test('non-approved providers are redirected to the signed account-status gate on login', function (ProviderStatusEnum $status): void {
    $provider = createGateProvider($status);

    $response = $this->from(route('provider.login'))
        ->post(route('provider.login.submit'), [
            'email' => $provider->email,
            'password' => 'password',
        ]);

    assertRedirectsToSignedAccountStatusGate($response, $provider);
    expect(auth('provider')->check())->toBeFalse();
})->with([
    ProviderStatusEnum::Pending,
    ProviderStatusEnum::Suspended,
    ProviderStatusEnum::Rejected,
    ProviderStatusEnum::Blocked,
    ProviderStatusEnum::SelfDeactivated,
]);

test('non-approved providers are redirected to the signed account-status gate by middleware mid-session', function (ProviderStatusEnum $status): void {
    $provider = createGateProvider($status);

    $response = $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class));

    assertRedirectsToSignedAccountStatusGate($response, $provider);
    expect(auth('provider')->check())->toBeFalse();
})->with([
    ProviderStatusEnum::Pending,
    ProviderStatusEnum::Suspended,
    ProviderStatusEnum::Rejected,
    ProviderStatusEnum::Blocked,
    ProviderStatusEnum::SelfDeactivated,
]);

test('approved providers still log in normally and are unaffected by the status gate', function (): void {
    $provider = createWalletProvider([
        'email' => 'approved-gate@example.com',
        'password' => 'password',
        'status' => ProviderStatusEnum::Approved,
    ]);

    $this->from(route('provider.login'))
        ->post(route('provider.login.submit'), [
            'email' => $provider->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('provider.home'));

    expect(auth('provider')->check())->toBeTrue();
});

test('wrong credentials still show an inline validation error on the login form', function (): void {
    $provider = createWalletProvider([
        'email' => 'wrong-pass@example.com',
        'password' => 'password',
        'status' => ProviderStatusEnum::Approved,
    ]);

    $this->from(route('provider.login'))
        ->post(route('provider.login.submit'), [
            'email' => $provider->email,
            'password' => 'not-the-password',
        ])
        ->assertRedirect(route('provider.login'))
        ->assertSessionHasErrors('email');

    expect(auth('provider')->check())->toBeFalse();
});

test('expired and invalid account-status signatures redirect to login', function (): void {
    $provider = createGateProvider(ProviderStatusEnum::Pending);

    $expired = URL::temporarySignedRoute(
        'provider.account-status',
        now()->subMinute(),
        ['provider' => $provider->id],
    );

    $this->get($expired)->assertRedirect(route('provider.login'));

    $this->get(route('provider.account-status', [
        'provider' => $provider->id,
        'signature' => 'invalid',
        'expires' => now()->addMinutes(15)->getTimestamp(),
    ]))->assertRedirect(route('provider.login'));
});

test('the gate page shows a suspend/reject reason when present and omits it when absent', function (): void {
    $withReason = createGateProvider(ProviderStatusEnum::Suspended, [
        'reason' => 'Incomplete commercial record',
    ]);
    $withoutReason = createGateProvider(ProviderStatusEnum::Suspended, [
        'reason' => null,
    ]);

    $withUrl = app(GenerateProviderAccountStatusGateUrlAction::class)->handle($withReason);
    $withoutUrl = app(GenerateProviderAccountStatusGateUrlAction::class)->handle($withoutReason);

    $this->get($withUrl)
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Provider/Auth/AccountStatusPage')
            ->where('status.value', ProviderStatusEnum::Suspended->value)
            ->where('reason', 'Incomplete commercial record')
            ->where('block_reason', null));

    $this->get($withoutUrl)
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Provider/Auth/AccountStatusPage')
            ->where('status.value', ProviderStatusEnum::Suspended->value)
            ->where('reason', null));
});

test('the gate page shows the latest block history reason for blocked providers', function (): void {
    $provider = createWalletProvider([
        'email' => 'blocked-reason@example.com',
        'password' => 'password',
        'status' => ProviderStatusEnum::Blocked,
    ]);
    $provider->block(3, 'Repeated policy violations');

    $url = app(GenerateProviderAccountStatusGateUrlAction::class)->handle($provider->fresh());

    $this->get($url)
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Provider/Auth/AccountStatusPage')
            ->where('status.value', ProviderStatusEnum::Blocked->value)
            ->where('is_temporary_block', true)
            ->where('block_reason', 'Repeated policy violations')
            ->where('reason', null));
});

test('an approved provider hitting a still-valid signed gate URL is redirected to login', function (): void {
    $provider = createGateProvider(ProviderStatusEnum::Pending);
    $url = app(GenerateProviderAccountStatusGateUrlAction::class)->handle($provider);

    $provider->forceFill(['status' => ProviderStatusEnum::Approved])->save();

    $this->get($url)->assertRedirect(route('provider.login'));
});
