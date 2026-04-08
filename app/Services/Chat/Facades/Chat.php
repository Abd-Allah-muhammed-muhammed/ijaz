<?php

namespace App\Services\Chat\Facades;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\TicketSupport;
use App\Services\Chat\Contracts\IChatService;
use App\Services\Chat\Features\MemberChat;
use App\Services\Chat\Features\OrderChat;
use App\Services\Chat\Features\SupportChat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static SupportChat  support(?TicketSupport $ticket = null):
 * @method static Collection  onlineUsers():
 * @method static MemberChat members(?\App\Models\Conversation $chat = null):
 * @method static OrderChat order(Order $order):
 * @method static Conversation generate(\App\Services\Chat\Contracts\HasConversation $user1, \App\Services\Chat\Contracts\HasConversation $user2)
 */
class Chat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return IChatService::class;
    }
}
