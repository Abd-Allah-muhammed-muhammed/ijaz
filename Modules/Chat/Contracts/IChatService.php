<?php

namespace Modules\Chat\Contracts;

use Illuminate\Support\Collection;
use Modules\Chat\Infrastructure\Features\MemberChat;
use Modules\Chat\Models\Conversation;
use Pusher\ApiErrorException;

interface IChatService
{
    /**
     * @throws ApiErrorException
     */
    public function onlineUsers(): Collection;

    public function members(Conversation $chat): MemberChat;
}
