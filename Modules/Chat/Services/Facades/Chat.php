<?php

namespace Modules\Chat\Services\Facades;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\TicketSupport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Modules\Chat\Contracts\IChatService;
use Modules\Chat\Infrastructure\Features\MemberChat;
use Modules\Chat\Infrastructure\Features\OrderChat;
use Modules\Chat\Infrastructure\Features\SupportChat;

/**
 * @method static SupportChat  support(?TicketSupport $ticket = null):
 * @method static Collection  onlineUsers():
 * @method static MemberChat members(?\App\Models\Conversation $chat = null):
 * @method static OrderChat order(Order $order):
 * @method static Conversation generate(\Modules\Chat\Contracts\HasConversation $user1, \Modules\Chat\Contracts\HasConversation $user2)
 */
class Chat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return IChatService::class;
    }
}
