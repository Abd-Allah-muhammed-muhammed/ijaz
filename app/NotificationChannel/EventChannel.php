<?php

namespace App\NotificationChannel;

use App\Services\Firebase\Contract\InteractWithFirebase;
use Arr;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Notifications\Notification;

class EventChannel
{
    /**
     * @throws GuzzleException
     */
    public function send(InteractWithFirebase $notifiable, Notification $notification): bool
    {
        $event = method_exists($notification, 'toEvent')
          ? $notification->toEvent($notifiable)
          : null;

        if (empty($event)) {
            return false;
        }
        foreach (Arr::wrap($event) as $value) {
            event($value);
        }

        return true;
    }
}
