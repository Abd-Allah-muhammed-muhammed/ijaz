<?php

namespace App\Console\Commands;

use App\Console\Commands\listeners\Context;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Chat\Models\System;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Message;

use function Ratchet\Client\connect;

class ListenToOnline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:online-listen {--debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected string $socket_id = '';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $system = System::firstOrCreate(
            [
                'id' => 1,
            ],
            [
                'name' => 'System',
                'online' => true,
            ]
        );

        $context = new Context(
            cache()
                ->rememberForever('system_token', fn () => $system->createToken('system')->plainTextToken)
        );
        $context->setCommand($this);
        $DS = DIRECTORY_SEPARATOR;
        $listeners = require app_path("Console{$DS}Commands{$DS}listeners{$DS}online.php");

        $key = config('broadcasting.connections.reverb.key');

        $host = 'localhost';
        $port = config('reverb.servers.reverb.port');
        $url = "ws://{$host}:{$port}/app/{$key}?protocol=7&client=js&version=8.4.0&flash=false";

        connect($url)->then(function (WebSocket $conn) use ($listeners, $context) {
            $conn->on('message', function (Message $msg) use ($conn, $listeners, $context) {
                $event = json_decode($msg, true);
                $this->handleEvent($event, $conn, $listeners, $context);
            });
        }, function ($e) {
            $this->logError("Could not connect: {$e->getMessage()}\n");
        });

        return 0;
    }

    public function handleEvent(array $event, WebSocket $connection, array $listeners, Context $context): void
    {
        $data = [];
        if (isset($event['data'])) {
            if (is_string($event['data'])) {
                $data = json_decode($event['data'], true);
            } else {
                $data = $event['data'];
            }
        }
        $event['data'] = $data;
        if (isset($listeners[$event['event']])) {
            $listeners[$event['event']]($event, $connection, $context);
        } else {
            $this->logError('No listener for event: '.$event['event']);
        }
    }

    public function logError(string $message): void
    {
        if ($this->option('debug')) {
            $this->error($message);
        }
    }

    /**
     * @throws ConnectionException
     */
    public function subscribeToOnline(WebSocket $conn, string $token, string $socket_id): void
    {
        $channel = (string) new PresenceChannel('online');
        $response = $this->authenticate($channel, $token, $socket_id);
        $event = json_decode($response, true);
        $event = [
            ...$event,
            'channel' => $channel,
        ];
        $conn->send(json_encode([
            'event' => 'pusher:subscribe',
            'data' => $event,
        ]));
    }

    /**
     * @throws ConnectionException
     */
    public function authenticate(string $channel_name, string $token, string $socket_id): string
    {
        $response = Http::withToken($token)->withHeader('accept', 'application/json')->post(url('api/broadcasting/auth'), [
            'socket_id' => $socket_id,
            'channel_name' => $channel_name,
        ]);

        return $response->body();
    }

    public function makeAllOnline(Collection $ids): void
    {
        $ids->groupBy(function (string &$user) {
            [$table, $id] = explode('-', $user);
            $user = $id;

            return $table;
        })->each(function ($value, $key) {
            $table = str($key)->plural();
            $ids = $value->toArray();
            DB::table($table)
                ->whereIn('id', $ids)
                ->where('online', false)
                ->update(['online' => true]);

            $this->log("{$table} updated with ids:".implode(',', $ids));
        });
    }

    public function log(string $message): void
    {
        if ($this->option('debug')) {
            $this->info($message);
        }
    }

    public function makeOnline(string $user_id): void
    {
        [$table, $id] = explode('-', $user_id);
        $table = str($table)->plural();
        DB::table($table)->where('id', $id)->update(['online' => true]);
        $this->log("{$user_id} added");
    }

    public function makeOffline(string $user_id): void
    {
        [$table, $id] = explode('-', $user_id);
        $table = str($table)->plural();
        DB::table($table)->where('id', $id)->update(['online' => false]);
        $this->log("{$user_id} removed");
    }
}
