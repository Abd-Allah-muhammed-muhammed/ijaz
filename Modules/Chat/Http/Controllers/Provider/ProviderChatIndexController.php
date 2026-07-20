<?php

namespace Modules\Chat\Http\Controllers\Provider;

use App\Enums\Order\OrderStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Response;
use Modules\Chat\Http\Resources\Dashboard\ConversationCollection;
use Modules\Chat\Models\Conversation;

class ProviderChatIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var Provider $provider */
        $provider = auth('provider')->user();

        $rows = Conversation::query()
            ->select('conversations.*')
            ->where('operation_type', Order::class)
            ->join('orders', function ($join) {
                $join->on('orders.id', 'conversations.operation_id')
                    ->where('orders.status', '!=', OrderStatusEnum::EndedByClient);
            })
            ->with(['lastMessage.sender', 'lastMessage.lastAttachment', 'user2', 'user1'])
            ->withCountUnreadMessagesFor($provider)
            ->where(function (Builder $query) use ($provider) {
                $query->whereMorphedTo('user1', $provider)
                    ->orWhereMorphedTo('user2', $provider);
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Provider/Chat/Index', [
            'prams' => $request->all() ?: [],
            'rows' => ConversationCollection::make($rows),
            'current_conversation' => $request->filled('conversation')
                ? $rows->firstWhere('id', $request->get('conversation'))
                : null,
        ]);
    }
}
