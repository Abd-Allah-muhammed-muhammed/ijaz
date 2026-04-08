<?php

use App\Console\Commands\listeners\Context;
use Illuminate\Broadcasting\PresenceChannel;
use Ratchet\Client\WebSocket;

return [
    'pusher:connection_established' => function (array $event, WebSocket $conn, Context $context) {
        $context->setSocketId($event['data']['socket_id']);
        $context->getCommand()->log('Connection established with socket id: '.$context->getSocketId());
        $context->getCommand()->subscribeToOnline($conn, $context->getToken(), $context->getSocketId());
    },
    'pusher:subscription_succeeded' => function (array $event, WebSocket $conn, Context $context) {
        $context->getCommand()->info(json_encode($event));
    },
    'pusher:ping' => function (array $event, WebSocket $conn, Context $context) {
        $context->getCommand()->log('Ping event: '.json_encode($event));

        $conn->send(json_encode([
            'event' => 'pusher:pong',
        ]));
        $context->getCommand()->log('Pong Pong sent');
    },
    'pusher:error' => function (array $event, WebSocket $conn, Context $context) {
        $context->getCommand()->logError(json_encode($event));

    },
    'pusher_internal:subscription_succeeded' => function (array $event, WebSocket $conn, Context $context) {
        switch ($event['channel']) {
            case (string) new PresenceChannel('online') :

                $context->getCommand()->makeAllOnline(collect($event['data']['presence']['ids']));
                break;

            default:
                return;
        }
    },
    'pusher_internal:member_added' => function (array $event, WebSocket $conn, Context $context) {
        switch ($event['channel']) {
            case (string) new PresenceChannel('online') :

                $context->getCommand()->makeOnline($event['data']['user_id']);
                break;

            default:
                return;
        }
    },
    'pusher_internal:member_removed' => function (array $event, WebSocket $conn, Context $context) {
        switch ($event['channel']) {
            case (string) new PresenceChannel('online') :

                $context->getCommand()->makeOffline($event['data']['user_id']);
                break;

            default:
                return;
        }
    },
];
