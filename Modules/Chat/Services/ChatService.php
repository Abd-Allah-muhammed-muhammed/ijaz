<?php

namespace Modules\Chat\Services;

use App\Models\Order;
use App\Models\TicketSupport;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Chat\Contracts\HasConversation;
use Modules\Chat\Contracts\IChatService;
use Modules\Chat\Infrastructure\Features\MemberChat;
use Modules\Chat\Infrastructure\Features\OrderChat;
use Modules\Chat\Infrastructure\Features\SupportChat;
use Modules\Chat\Models\Conversation;
use Pusher\ApiErrorException;

class ChatService implements IChatService
{
    public static function generate(HasConversation $user1, HasConversation $user2): Conversation
    {
        $chat = Conversation::where(function (Builder $query) use ($user1, $user2) {
            $query->where('user1_type', get_class($user1))->where('user1_id', $user1->getKey())
                ->where('user2_type', get_class($user2))->where('user2_id', $user2->getKey());
        })->Orwhere(function (Builder $query) use ($user2, $user1) {
            $query->where('user1_type', get_class($user2))->where('user1_id', $user2->getKey())
                ->where('user2_type', get_class($user1))->where('user2_id', $user1->getKey());
        })->first();
        if (! $chat) {
            return Conversation::create([
                'user1_type' => get_class($user1),
                'user1_id' => $user1->getKey(),
                'user2_type' => get_class($user2),
                'user2_id' => $user2->getKey(),
            ]);
        }

        return $chat;
    }

    public function order(Order $order): OrderChat
    {
        return new OrderChat($order);
    }

    public function support(TicketSupport $ticket): SupportChat
    {
        return new SupportChat($ticket);
    }

    /**
     * @throws ApiErrorException
     */
    public function onlineUsers(): Collection
    {
        try {
            return collect(app(BroadcastManager::class)
                ->getPusher()
                ->get_users_info('presence-online')
                ->users)
                ->pluck('id');
        } catch (ApiErrorException $e) {
            if ($e->getCode() != 404) {
                throw $e;
            }

            return collect();
        }

    }

    public function members(Conversation $chat): MemberChat
    {
        return new MemberChat($chat);
    }
}
