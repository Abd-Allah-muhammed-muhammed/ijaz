<?php

use App\Actions\DeviceToken\RegisterDeviceTokenAction;
use App\Models\DeviceToken;
use App\NotificationChannels\FirebaseChannel;
use App\Notifications\AccountStatusChangedNotification;
use App\Services\Auth\ProviderAuthService;
use App\Services\Firebase\FirebaseService;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Notifications\ReviewReceivedNotification;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

test('provider can register a web FCM token via the new endpoint', function () {
    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->postJson(route('provider.device-tokens.store'), [
            'token' => 'web-fcm-token-abc',
        ])
        ->assertSuccessful();

    expect(DeviceToken::query()->where('token', 'web-fcm-token-abc')->first())
        ->not->toBeNull()
        ->platform->toBe('web')
        ->and($provider->fresh()->deviceTokens()->where('token', 'web-fcm-token-abc')->exists())->toBeTrue();
});

test('registering a web token stores platform as web and is idempotent on re-registration', function () {
    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->postJson(route('provider.device-tokens.store'), [
            'token' => 'web-fcm-token-idempotent',
        ])
        ->assertSuccessful();

    $this->actingAs($provider, 'provider')
        ->postJson(route('provider.device-tokens.store'), [
            'token' => 'web-fcm-token-idempotent',
        ])
        ->assertSuccessful();

    expect(DeviceToken::query()->where('token', 'web-fcm-token-idempotent')->count())->toBe(1)
        ->and(DeviceToken::query()->where('token', 'web-fcm-token-idempotent')->value('platform'))->toBe('web');
});

test('a StatusChangedNotification with sendsFirebase enabled reaches a web-registered device token the same way it reaches a mobile token', function () {
    $provider = createWalletProvider();
    app(RegisterDeviceTokenAction::class)->handle($provider, 'provider-web-fcm-token', 'web');

    $this->mock(FirebaseService::class, function (MockInterface $mock) {
        $mock->shouldReceive('send')
            ->once()
            ->withArgs(function ($outgoing): bool {
                return $outgoing->targetType === 'token'
                    && $outgoing->targetValue === 'provider-web-fcm-token';
            });
    });

    $notification = new AccountStatusChangedNotification($provider, 'approved');

    expect($notification->via($provider))->toContain('firebase');

    $channel = app(FirebaseChannel::class);

    expect($channel->send($provider->fresh(), $notification))->toBeTrue();
});

test('provider StatusChanged and Review notifications include the firebase channel', function () {
    $provider = createWalletProvider();

    expect((new AccountStatusChangedNotification($provider, 'approved'))->via($provider))
        ->toBe(['database', 'broadcast', 'firebase'])
        ->and((new ReviewReceivedNotification(new Review([
            'id' => 1,
            'rating' => 5,
            'operation_type' => 'order',
            'operation_id' => 1,
        ])))->via($provider))
        ->toBe(['database', 'broadcast', 'firebase']);
});

test('provider logout clears only the session web FCM token, not other device tokens', function () {
    $provider = createWalletProvider();

    app(RegisterDeviceTokenAction::class)->handle($provider, 'mobile-keep-me', 'android');
    app(RegisterDeviceTokenAction::class)->handle($provider, 'other-browser-web', 'web');
    app(RegisterDeviceTokenAction::class)->handle($provider, 'session-web-token', 'web');

    $this->actingAs($provider, 'provider');

    $request = Request::create('/provider/logout', 'POST');
    $request->setLaravelSession($this->app['session']->driver());
    $request->session()->put('provider_web_fcm_token', 'session-web-token');

    app(ProviderAuthService::class)->logout($request);

    expect($provider->fresh()->deviceTokens()->pluck('token')->sort()->values()->all())
        ->toBe(['mobile-keep-me', 'other-browser-web'])
        ->and(auth('provider')->check())->toBeFalse();
});

test('guest cannot register a web FCM token', function () {
    $this->postJson(route('provider.device-tokens.store'), [
        'token' => 'guest-token',
    ])->assertUnauthorized();
});
