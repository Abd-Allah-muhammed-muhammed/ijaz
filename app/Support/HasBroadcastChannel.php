<?php

namespace App\Support;

trait HasBroadcastChannel
{
    public function receivesBroadcastNotificationsOn(): string
    {
        return $this->getAuthIdentifierForBroadcasting();
    }

    public function getAuthIdentifierForBroadcasting(): string
    {
        return $this->getType().'-'.$this->getKey();
    }

    abstract public function getType(): string;

    abstract public function getKey();
}
