<?php

namespace Modules\Chat\Contracts;

use Illuminate\Support\Collection;
use Modules\Chat\Infrastructure\Features\MemberChat;
use Modules\Chat\Infrastructure\Features\OrderChat;
use Modules\Chat\Models\Conversation;
use Modules\Orders\Models\Order;
use Pusher\ApiErrorException;

interface IChatService
{
    /**
     * @throws ApiErrorException
     */
    public function onlineUsers(): Collection;

    public function members(Conversation $chat): MemberChat;

    public function order(Order $order): OrderChat;
}
