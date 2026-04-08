<?php

namespace App\Console\Commands\listeners;

use App\Console\Commands\ListenToOnline;

class Context
{
    protected string $socket_id = '';

    protected ListenToOnline $command;

    public function __construct(protected string $token) {}

    public function getSocketId(): string
    {
        return $this->socket_id;
    }

    public function setSocketId(string $socket_id): void
    {
        $this->socket_id = $socket_id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCommand(): ListenToOnline
    {
        return $this->command;
    }

    public function setCommand(ListenToOnline $command): void
    {
        $this->command = $command;
    }
}
