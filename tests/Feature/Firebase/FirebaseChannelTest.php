<?php

use App\Models\Provider;
use App\Models\User;
use App\NotificationChannels\FirebaseChannel;
use App\Notifications\DomainNotification;
use App\Services\Firebase\DTO\Message;
use App\Services\Firebase\FirebaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

beforeEach(function () {
    Cache::flush();

    $privateKey = file_get_contents(base_path('tests/Fixtures/firebase-test-private.pem'));
    $path = tempnam(sys_get_temp_dir(), 'firebase-channel-creds-').'.json';

    file_put_contents($path, json_encode([
        'project_id' => 'ijaz-test-project',
        'client_email' => 'firebase-adminsdk@ijaz-test-project.iam.gserviceaccount.com',
        'private_key' => $privateKey,
        'type' => 'service_account',
    ], JSON_THROW_ON_ERROR));

    $this->firebaseChannelCredentialsPath = $path;

    config()->set('services.firebase', [
        'credentials' => $path,
        'cache_key' => 'firebase-oauth-token-channel-test',
        'token_ttl_skew_seconds' => 180,
        'oauth_token_url' => 'https://oauth2.googleapis.com/token',
        'fcm_send_url' => 'https://fcm.googleapis.com/v1/projects/{project_id}/messages:send',
        'http_timeout' => 5,
    ]);
});

afterEach(function () {
    if (isset($this->firebaseChannelCredentialsPath) && is_file($this->firebaseChannelCredentialsPath)) {
        unlink($this->firebaseChannelCredentialsPath);
    }
});

test('firebase channel returns false gracefully for a notifiable with no valid token/topic', function () {
    $provider = new Provider;
    $provider->id = 1;

    $firebase = $this->mock(FirebaseService::class, function (MockInterface $mock) {
        $mock->shouldNotReceive('send');
    });

    $channel = new FirebaseChannel($firebase);

    $notification = new class extends DomainNotification
    {
        protected function titleKey(): string
        {
            return 'title';
        }

        protected function bodyKey(): string
        {
            return 'body';
        }

        protected function payload(): array
        {
            return [];
        }

        protected function firebaseData(object $notifiable): array
        {
            return [];
        }

        public function broadcastType(): string
        {
            return 'test';
        }

        public function toFirebase(object $notifiable): Message
        {
            return Message::make('Title', 'Body');
        }
    };

    expect($channel->send($provider, $notification))->toBeFalse();
});

test('firebase channel returns false when the notification message is invalid', function () {
    $user = User::factory()->make([
        'id' => 10,
        'player_id' => 'valid-device-token',
    ]);

    $firebase = $this->mock(FirebaseService::class, function (MockInterface $mock) {
        $mock->shouldNotReceive('send');
    });

    $channel = new FirebaseChannel($firebase);

    $notification = new class extends DomainNotification
    {
        protected function titleKey(): string
        {
            return 'title';
        }

        protected function bodyKey(): string
        {
            return 'body';
        }

        protected function payload(): array
        {
            return [];
        }

        protected function firebaseData(object $notifiable): array
        {
            return [];
        }

        public function broadcastType(): string
        {
            return 'test';
        }

        public function toFirebase(object $notifiable): Message
        {
            return Message::make('', '');
        }
    };

    expect($channel->send($user, $notification))->toBeFalse();
});

test('firebase channel sends an outgoing message for a user with a valid player_id', function () {
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'ya29.channel-token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ], 200),
        'fcm.googleapis.com/*' => Http::response(['name' => 'projects/ijaz-test-project/messages/1'], 200),
    ]);

    $user = User::factory()->make([
        'id' => 99,
        'player_id' => 'player-xyz',
        'language' => 'en',
    ]);

    $notification = new class extends DomainNotification
    {
        protected function titleKey(): string
        {
            return 'order_offer_created';
        }

        protected function bodyKey(): string
        {
            return 'order_offer_has_been_created';
        }

        protected function payload(): array
        {
            return ['order_id' => 1];
        }

        protected function firebaseData(object $notifiable): array
        {
            return ['order_id' => 1];
        }

        protected function sendsFirebase(object $notifiable): bool
        {
            return true;
        }

        public function broadcastType(): string
        {
            return 'order offer created';
        }
    };

    $sent = app(FirebaseChannel::class)->send($user, $notification);

    expect($sent)->toBeTrue();

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 'fcm.googleapis.com')) {
            return false;
        }

        $message = $request['message'];

        return $message['token'] === 'player-xyz'
            && $message['notification']['title'] === trans('order_offer_created', locale: 'en')
            && $message['data']['order_id'] === '1';
    });
});
