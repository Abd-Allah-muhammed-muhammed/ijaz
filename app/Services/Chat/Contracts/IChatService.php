<?php

namespace App\Services\Chat\Contracts;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\TicketSupport;
use App\Services\Chat\Features\MemberChat;
use App\Services\Chat\Features\OrderChat;
use App\Services\Chat\Features\SupportChat;
use Illuminate\Support\Collection;
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
