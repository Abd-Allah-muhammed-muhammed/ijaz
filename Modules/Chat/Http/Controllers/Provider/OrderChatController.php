<?php

namespace Modules\Chat\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\Actions\ListConversationsAction;
use Modules\Chat\Actions\ListMessagesAction;
use Modules\Chat\Actions\OpenConversationAction;
use Modules\Chat\Actions\SendMessageAction;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Exceptions\ChatException;
use Modules\Chat\Http\Requests\SendMessageRequest;
use Modules\Chat\Http\Requests\StoreOrderChatRequest;
use Modules\Chat\Http\Resources\ConversationCollection;
use Modules\Chat\Http\Resources\ConversationMessageCollection;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\ConversationResource;
use Modules\Chat\Registry\ChatTypeRegistry;

class OrderChatController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly OpenConversationAction $openAction,
        private readonly ListConversationsAction $listAction,
        private readonly ListMessagesAction $listMessagesAction,
        private readonly SendMessageAction $sendAction,
        private readonly ChatTypeRegistry $registry,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $handler = $this->registry->get(ChatTypeEnum::Order);

        return $this->successResponse(
            ConversationCollection::make(
                $this->listAction->handle(
                    auth('provider')->user(),
                    $handler,
                    $request->integer('per_page', 15),
                )
            )
        );
    }

    public function store(StoreOrderChatRequest $request): JsonResponse
    {
        $order = Order::query()->findOrFail($request->validated('order_id'));
        $handler = $this->registry->get(ChatTypeEnum::Order);

        try {
            $conversation = $this->openAction->handle(auth('provider')->user(), $order, $handler);
        } catch (ChatException) {
            return $this->failedResponse(
                errors: [],
                message: 'not found',
                statusCode: 404,
            );
        }

        return $this->successResponse(
            ConversationResource::make(
                $conversation->load(['lastMassage.sender', 'lastMassage.lastAttachment', 'user2', 'user1'])
            )
        );
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return $this->successResponse(
            ConversationMessageCollection::make(
                $this->listMessagesAction->handle(
                    $conversation,
                    auth('provider')->user(),
                    $request->integer('per_page', 15),
                )
            )
        );
    }

    public function send(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('send', $conversation);

        $handler = $this->registry->get(ChatTypeEnum::Order);
        $message = $this->sendAction->handle(
            $conversation,
            auth('provider')->user(),
            ChatMessageData::fromRequest($request),
            $handler,
        );

        return $this->successResponse(
            ConversationMessageResource::make($message->loadMissing(['sender', 'attachments']))
        );
    }
}
