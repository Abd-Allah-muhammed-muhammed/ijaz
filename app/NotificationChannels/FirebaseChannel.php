<?php

namespace App\NotificationChannels;

use App\Services\Firebase\Contract\InteractWithFirebase;
use App\Services\Firebase\DTO\FirebaseMessageTarget;
use App\Services\Firebase\DTO\FirebaseNotificationContent;
use App\Services\Firebase\DTO\OutgoingFirebaseMessage;
use App\Services\Firebase\Exceptions\FirebaseAuthenticationException;
use App\Services\Firebase\Exceptions\FirebaseConfigurationException;
use App\Services\Firebase\Exceptions\FirebaseSendException;
use App\Services\Firebase\FirebaseService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

readonly class FirebaseChannel
{
    public function __construct(protected FirebaseService $firebaseService) {}

    /**
     * Deliver via FCM to every registered target. Typed Firebase failures are logged
     * per-token and swallowed so they cannot fail sibling channels or block the job.
     * Unrelated exceptions still propagate.
     */
    public function send(InteractWithFirebase $notifiable, Notification $notification): bool
    {
        $targets = $this->normalizeTargets($notifiable->routeNotificationForFirebase());

        if ($targets === []) {
            return false;
        }

        $message = method_exists($notification, 'toFirebase')
            ? $notification->toFirebase($notifiable)
            : FirebaseNotificationContent::make('', '');

        if (! $message instanceof FirebaseNotificationContent || $message->isNotValid()) {
            return false;
        }

        $anySucceeded = false;

        foreach ($targets as $target) {
            if ($target->isNotValid()) {
                continue;
            }

            $outgoing = new OutgoingFirebaseMessage(
                title: $message->getTitle(),
                body: $message->getBody(),
                targetType: $target->getType(),
                targetValue: $target->getValue(),
                data: array_merge($message->getData(), [
                    'title' => $message->getTitle(),
                    'body' => $message->getBody(),
                ]),
            );

            try {
                $this->firebaseService->send($outgoing);
                $anySucceeded = true;
            } catch (FirebaseConfigurationException|FirebaseAuthenticationException|FirebaseSendException $exception) {
                Log::warning('Firebase notification channel failed; continuing without push', [
                    'notification' => $notification::class,
                    'notifiable_type' => $notifiable::class,
                    'notifiable_id' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
                    'target_type' => $target->getType(),
                    'exception' => $exception->getMessage(),
                    'status' => $exception instanceof FirebaseAuthenticationException
                        || $exception instanceof FirebaseSendException
                        ? $exception->status
                        : null,
                ]);
            }
        }

        return $anySucceeded;
    }

    /**
     * @param  FirebaseMessageTarget|iterable<int, FirebaseMessageTarget>  $targets
     * @return list<FirebaseMessageTarget>
     */
    private function normalizeTargets(FirebaseMessageTarget|iterable $targets): array
    {
        if ($targets instanceof FirebaseMessageTarget) {
            return [$targets];
        }

        $normalized = [];

        foreach ($targets as $target) {
            if ($target instanceof FirebaseMessageTarget) {
                $normalized[] = $target;
            }
        }

        return $normalized;
    }
}
