<?php

namespace App\NotificationChannel;

use App\Services\Firebase\Contract\InteractWithFirebase;
use App\Services\Firebase\DTO\Message;
use App\Services\Firebase\FirebaseService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Notifications\Notification;

readonly class FirebaseChannel
{
    public function __construct(protected FirebaseService $firebaseService) {}

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function send(InteractWithFirebase $notifiable, Notification $notification): bool
    {
        $target = $notifiable->routeNotificationForFirebase();

        if ($target->isNotValid()) {
            return false;
        }

        /**
         * @var Message $message
         */
        $message = method_exists($notification, 'toFirebase')
          ? $notification->toFirebase($notifiable)
          : Message::make('', '');

        if ($message->isNotValid()) {
            return false;
        }

        $this->firebaseService
            ->message($message->getTitle(), $message->getBody())
            ->data(array_merge($message->getData(), [
                'title' => $message->getTitle(),
                'body' => $message->getBody(),
            ]))
            ->target($target->getType(), $target->getValue())
            ->send();

        return true;
    }
}
