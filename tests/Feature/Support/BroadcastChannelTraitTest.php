<?php

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use App\Support\HasBroadcastChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Notifications\Notification;
use Modules\Chat\Infrastructure\Events\NewNotificationSendEvent;
use Modules\Chat\Models\System;

it('applies HasBroadcastChannel to User, Provider, Admin, and System', function () {
    expect(class_uses_recursive(User::class))->toContain(HasBroadcastChannel::class)
        ->and(class_uses_recursive(Provider::class))->toContain(HasBroadcastChannel::class)
        ->and(class_uses_recursive(Admin::class))->toContain(HasBroadcastChannel::class)
        ->and(class_uses_recursive(System::class))->toContain(HasBroadcastChannel::class);
});

it('resolves receivesBroadcastNotificationsOn for all broadcast actor types', function () {
    $user = User::factory()->create();
    $provider = createWalletProvider();
    $admin = createOrdersAdmin();
    $system = ensureSystemExists();

    expect($user->receivesBroadcastNotificationsOn())->toBe('user-'.$user->getKey())
        ->and($provider->receivesBroadcastNotificationsOn())->toBe('provider-'.$provider->getKey())
        ->and($admin->receivesBroadcastNotificationsOn())->toBe('admin-'.$admin->getKey())
        ->and($system->receivesBroadcastNotificationsOn())->toBe('system-'.$system->getKey());
});

it('uses receivesBroadcastNotificationsOn when Laravel builds broadcast notification channels', function () {
    $user = User::factory()->create();
    $provider = createWalletProvider();
    $admin = createOrdersAdmin();
    $system = ensureSystemExists();

    $notification = new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['broadcast'];
        }

        /**
         * @return array<string, mixed>
         */
        public function toArray(object $notifiable): array
        {
            return [];
        }
    };

    foreach ([$user, $provider, $admin, $system] as $actor) {
        $event = new BroadcastNotificationCreated($actor, $notification, []);
        $channels = $event->broadcastOn();

        expect($channels)->toHaveCount(1)
            ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
            ->and($channels[0]->name)->toBe('private-'.$actor->receivesBroadcastNotificationsOn());
    }
});

it('uses receivesBroadcastNotificationsOn when NewNotificationSendEvent builds channels', function () {
    $user = User::factory()->create();

    $event = new NewNotificationSendEvent($user, 3);
    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe('private-'.$user->receivesBroadcastNotificationsOn());
});
