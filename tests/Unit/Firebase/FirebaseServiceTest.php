<?php

use App\Services\Firebase\DTO\OutgoingFirebaseMessage;
use App\Services\Firebase\Exceptions\FirebaseConfigurationException;
use App\Services\Firebase\Exceptions\FirebaseSendException;
use App\Services\Firebase\FirebaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * @return string Absolute path to a temp service-account JSON using the shared test PEM.
 */
function firebaseTestCredentialsPath(): string
{
    $privateKey = file_get_contents(base_path('tests/Fixtures/firebase-test-private.pem'));

    $path = tempnam(sys_get_temp_dir(), 'firebase-creds-').'.json';

    file_put_contents($path, json_encode([
        'project_id' => 'ijaz-test-project',
        'client_email' => 'firebase-adminsdk@ijaz-test-project.iam.gserviceaccount.com',
        'private_key' => $privateKey,
        'type' => 'service_account',
    ], JSON_THROW_ON_ERROR));

    return $path;
}

beforeEach(function () {
    Cache::flush();

    $this->firebaseCredentialsPath = firebaseTestCredentialsPath();

    config()->set('services.firebase', [
        'credentials' => $this->firebaseCredentialsPath,
        'cache_key' => 'firebase-oauth-token-test',
        'token_ttl_skew_seconds' => 180,
        'oauth_token_url' => 'https://oauth2.googleapis.com/token',
        'fcm_send_url' => 'https://fcm.googleapis.com/v1/projects/{project_id}/messages:send',
        'http_timeout' => 5,
    ]);
});

afterEach(function () {
    if (isset($this->firebaseCredentialsPath) && is_file($this->firebaseCredentialsPath)) {
        unlink($this->firebaseCredentialsPath);
    }
});

test('firebase service sends a correctly structured FCM message', function () {
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'ya29.test-access-token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ], 200),
        'fcm.googleapis.com/*' => Http::response([
            'name' => 'projects/ijaz-test-project/messages/msg-1',
        ], 200),
    ]);

    $payload = OutgoingFirebaseMessage::toToken(
        token: 'device-token-abc',
        title: 'Hello',
        body: 'World',
        data: [
            'title' => 'Hello',
            'body' => 'World',
            'order_id' => 42,
        ],
    );

    $result = app(FirebaseService::class)->send($payload);

    expect($result)->toHaveKey('name', 'projects/ijaz-test-project/messages/msg-1');

    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://fcm.googleapis.com/v1/projects/ijaz-test-project/messages:send') {
            return false;
        }

        $message = $request['message'];

        return $request->hasHeader('Authorization', 'Bearer ya29.test-access-token')
            && $message['notification']['title'] === 'Hello'
            && $message['notification']['body'] === 'World'
            && $message['token'] === 'device-token-abc'
            && $message['data']['title'] === 'Hello'
            && $message['data']['body'] === 'World'
            && $message['data']['order_id'] === '42'
            && ! array_key_exists('android', $message)
            && ! array_key_exists('apns', $message);
    });
});

test('firebase service caches the oauth token with the correct TTL', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-06 10:00:00'));

    Http::fake([
        'oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'ya29.ttl-token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ], 200),
        'fcm.googleapis.com/*' => Http::response(['name' => 'ok'], 200),
    ]);

    app(FirebaseService::class)->send(
        OutgoingFirebaseMessage::toToken('tok', 'T', 'B'),
    );

    $cacheKey = config('services.firebase.cache_key');

    expect(Cache::get($cacheKey))->toBe('ya29.ttl-token');

    // expires_in 3600 − skew 180 = 3420 seconds. Still cached just before expiry.
    Carbon::setTestNow(now()->addSeconds(3419));
    expect(Cache::get($cacheKey))->toBe('ya29.ttl-token');

    // Past skewed TTL — cache entry must be gone (proves TTL was 3420, not 3580).
    Carbon::setTestNow(Carbon::parse('2026-08-06 10:00:00')->addSeconds(3421));
    expect(Cache::get($cacheKey))->toBeNull();

    Carbon::setTestNow();
});

test('firebase service reuses a cached valid token instead of requesting a new one', function () {
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'ya29.first-token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ], 200),
        'fcm.googleapis.com/*' => Http::response(['name' => 'ok'], 200),
    ]);

    $service = app(FirebaseService::class);
    $message = OutgoingFirebaseMessage::toToken('tok', 'T', 'B');

    $service->send($message);
    $service->send($message);

    Http::assertSentCount(3); // 1 OAuth + 2 FCM

    $oauthCount = Http::recorded()
        ->filter(fn ($pair) => str_contains($pair[0]->url(), 'oauth2.googleapis.com/token'))
        ->count();

    expect($oauthCount)->toBe(1);
});

test('firebase service handles an FCM API error response with a typed exception', function () {
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'ya29.err-token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ], 200),
        'fcm.googleapis.com/*' => Http::response([
            'error' => [
                'code' => 404,
                'message' => 'Requested entity was not found.',
                'status' => 'NOT_FOUND',
            ],
        ], 404),
    ]);

    expect(fn () => app(FirebaseService::class)->send(
        OutgoingFirebaseMessage::toToken('bad-token', 'T', 'B'),
    ))->toThrow(function (FirebaseSendException $exception) {
        expect($exception->getMessage())->toContain('Requested entity was not found.')
            ->and($exception->status)->toBe(404)
            ->and($exception->context)->toHaveKey('response');
    });
});

test('firebase service does not leak state between two independent sends', function () {
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'ya29.shared-token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ], 200),
        'fcm.googleapis.com/*' => Http::response(['name' => 'ok'], 200),
    ]);

    $service = app(FirebaseService::class);

    $service->send(OutgoingFirebaseMessage::toToken(
        token: 'token-one',
        title: 'First title',
        body: 'First body',
        data: ['kind' => 'first'],
    ));

    $service->send(OutgoingFirebaseMessage::toTopic(
        topic: 'news',
        title: 'Second title',
        body: 'Second body',
        data: ['kind' => 'second'],
    ));

    $fcmRequests = Http::recorded()
        ->filter(fn ($pair) => str_contains($pair[0]->url(), 'fcm.googleapis.com'))
        ->values();

    expect($fcmRequests)->toHaveCount(2);

    $first = $fcmRequests[0][0]['message'];
    $second = $fcmRequests[1][0]['message'];

    expect($first['token'])->toBe('token-one')
        ->and($first)->not->toHaveKey('topic')
        ->and($first['notification']['title'])->toBe('First title')
        ->and($first['data']['kind'])->toBe('first')
        ->and($second['topic'])->toBe('news')
        ->and($second)->not->toHaveKey('token')
        ->and($second['notification']['title'])->toBe('Second title')
        ->and($second['data']['kind'])->toBe('second');
});

test('firebase service throws a configuration exception when credentials are missing', function () {
    config()->set('services.firebase.credentials', storage_path('missing-firebase-credentials.json'));

    expect(fn () => app(FirebaseService::class)->send(
        OutgoingFirebaseMessage::toToken('tok', 'T', 'B'),
    ))->toThrow(FirebaseConfigurationException::class);
});
