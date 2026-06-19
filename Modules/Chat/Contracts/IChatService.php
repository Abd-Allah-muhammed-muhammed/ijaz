<?php

namespace Modules\Chat\Contracts;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\TicketSupport;
use Illuminate\Support\Collection;
use Modules\Chat\Infrastructure\Features\MemberChat;
use Modules\Chat\Infrastructure\Features\OrderChat;
use Modules\Chat\Infrastructure\Features\SupportChat;
use Pusher\ApiErrorException;

interface IChatService
{
    public function support(TicketSupport $ticket): SupportChat;

    /**
     * @throws ApiErrorException
     */
    public function onlineUsers(): Collection;

    public function members(Conversation $chat): MemberChat;

    public function order(Order $order): OrderChat;
}
