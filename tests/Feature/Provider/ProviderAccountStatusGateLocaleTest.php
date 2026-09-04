<?php

use App\Actions\Auth\Provider\GenerateProviderAccountStatusGateUrlAction;
use App\Enums\Providers\ProviderStatusEnum;
use App\Http\Controllers\Provider\AccountStatusController;
use App\Http\Controllers\Provider\HomeController;
use App\Models\Provider;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;

/**
 * Regression for the locale-prefix × signed-URL bug.
 *
 * Full HTTP `$this->get('/en/...')` cannot re-register mcamara's
 * `prefix => setLocale()` routes inside PHPUnit (routes boot once without a
 * locale segment). These tests therefore exercise the *real* locale middleware
 * classes + gate controller against a locale-baked signed URL — the same
 * objects that run in production — while curl verifies the Apache/FPM path.
 */
beforeEach(function (): void {
    // Route matching still uses locale-less registration in PHPUnit; disable only
    // the global rewrite on `$this->post` / `$this->get` helpers. Middleware under
    // test is invoked explicitly below.
    withoutOrdersLocaleMiddleware();
});

function createLocaleGateProvider(ProviderStatusEnum $status): Provider
{
    $provider = createWalletProvider([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'status' => $status,
    ]);

    if ($status === ProviderStatusEnum::Blocked) {
        $provider->block(5, 'blocked for locale gate test');
    }

    return $provider->fresh();
}

function invokeGateThroughLocaleMiddleware(string $url, Provider $provider)
{
    $request = Request::create($url, 'GET');
    $request->setLaravelSession(app('session')->driver());

    $pipeline = function (Request $request) use ($provider) {
        $response = app(AccountStatusController::class)->show($request, $provider);

        if ($response instanceof Responsable) {
            return $response->toResponse($request);
        }

        return $response;
    };

    $throughLocalizationRedirect = function (Request $request) use ($pipeline) {
        return app(LaravelLocalizationRedirectFilter::class)->handle($request, $pipeline);
    };

    $response = app(LocaleSessionRedirect::class)->handle($request, $throughLocalizationRedirect);

    return test()->createTestResponse($response, $request);
}

dataset('locale_gate_statuses', [
    ProviderStatusEnum::Pending,
    ProviderStatusEnum::Suspended,
    ProviderStatusEnum::Rejected,
    ProviderStatusEnum::Blocked,
    ProviderStatusEnum::SelfDeactivated,
]);

dataset('locale_gate_locales', [
    'en',
    'ar',
]);

test('login redirect Location is locale-baked and survives real locale middleware for each status', function (string $locale, ProviderStatusEnum $status): void {
    LaravelLocalization::setLocale($locale);
    app()->setLocale($locale);

    $provider = createLocaleGateProvider($status);

    $response = $this->from(route('provider.login'))
        ->post(route('provider.login.submit'), [
            'email' => $provider->email,
            'password' => 'password',
        ]);

    $response->assertRedirect();
    $location = (string) $response->headers->get('Location');

    expect($location)
        ->toContain('/'.$locale.'/provider/account-status/'.$provider->id)
        ->toContain('signature=')
        ->and(Request::create($location, 'GET')->hasValidSignature())->toBeTrue();

    invokeGateThroughLocaleMiddleware($location, $provider)
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Provider/Auth/AccountStatusPage')
            ->where('status.value', $status->value));

    expect(auth('provider')->check())->toBeFalse();
})->with('locale_gate_locales')->with('locale_gate_statuses');

test('mid-session gate redirect Location survives real locale middleware for each status', function (string $locale, ProviderStatusEnum $status): void {
    LaravelLocalization::setLocale($locale);
    app()->setLocale($locale);

    $provider = createLocaleGateProvider($status);

    $response = $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class));

    $response->assertRedirect();
    $location = (string) $response->headers->get('Location');

    expect($location)
        ->toContain('/'.$locale.'/provider/account-status/'.$provider->id)
        ->and(Request::create($location, 'GET')->hasValidSignature())->toBeTrue();

    invokeGateThroughLocaleMiddleware($location, $provider)
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Provider/Auth/AccountStatusPage')
            ->where('status.value', $status->value));
})->with('locale_gate_locales')->with('locale_gate_statuses');

test('locale-baked signed gate URLs are not redirected by locale middleware', function (string $locale): void {
    LaravelLocalization::setLocale($locale);
    app()->setLocale($locale);

    $provider = createLocaleGateProvider(ProviderStatusEnum::Pending);
    $url = app(GenerateProviderAccountStatusGateUrlAction::class)->handle($provider);

    expect($url)->toContain('/'.$locale.'/provider/account-status/'.$provider->id);

    $request = Request::create($url, 'GET');
    $request->setLaravelSession(app('session')->driver());

    $localeSessionResponse = app(LocaleSessionRedirect::class)->handle(
        $request,
        fn (Request $req) => response('passed-locale-session'),
    );

    expect($localeSessionResponse)->not->toBeInstanceOf(RedirectResponse::class)
        ->and($localeSessionResponse->getContent())->toBe('passed-locale-session');

    $localizationFilterResponse = app(LaravelLocalizationRedirectFilter::class)->handle(
        $request,
        fn (Request $req) => response('passed-localization-filter'),
    );

    expect($localizationFilterResponse)->not->toBeInstanceOf(RedirectResponse::class)
        ->and($localizationFilterResponse->getContent())->toBe('passed-localization-filter');

    invokeGateThroughLocaleMiddleware($url, $provider)
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Provider/Auth/AccountStatusPage')
            ->where('status.value', ProviderStatusEnum::Pending->value));
})->with('locale_gate_locales');
