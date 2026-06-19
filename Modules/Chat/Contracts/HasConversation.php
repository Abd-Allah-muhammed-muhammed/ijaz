<?php

namespace Modules\Chat\Contracts;

interface HasConversation
{
    public function getKey();

    public function getType();

    public function getAuthIdentifierForBroadcasting();

    public function getImageUrl(): string;
}
