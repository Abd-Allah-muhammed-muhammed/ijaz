<?php

namespace App\NotificationChannels;

use App\Services\Firebase\Contract\InteractWithFirebase;
use App\Services\Firebase\DTO\Message;
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
     * @throws FirebaseConfigurationException
     * @throws FirebaseAuthenticationException
     * @throws FirebaseSendException
     */
    public function send(InteractWithFirebase $notifiable, Notification $notification): bool
    {
        $target = $notifiable->routeNotificationForFirebase();

        if ($target->isNotValid()) {
            return false;
        }

        $message = method_exists($notification, 'toFirebase')
            ? $notification->toFirebase($notifiable)
            : Message::make('', '');

        if (! $message instanceof Message || $message->isNotValid()) {
            return false;
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
        } catch (FirebaseConfigurationException|FirebaseAuthenticationException|FirebaseSendException $exception) {
            Log::error('Firebase notification channel failed', [
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

            throw $exception;
        }

        return true;
    }
}
