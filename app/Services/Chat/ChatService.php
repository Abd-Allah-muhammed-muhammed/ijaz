<?php

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\TicketSupport;
use App\Services\Chat\Contracts\HasConversation;
use App\Services\Chat\Contracts\IChatService;
use App\Services\Chat\Features\MemberChat;
use App\Services\Chat\Features\OrderChat;
use App\Services\Chat\Features\SupportChat;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
