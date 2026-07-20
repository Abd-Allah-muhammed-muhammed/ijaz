<?php

namespace Modules\Chat\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Exceptions\ChatException;
use Modules\Chat\Http\Requests\SendMessageRequest;
use Modules\Chat\Http\Requests\StoreOrderChatRequest;
use Modules\Chat\Http\Resources\ConversationCollection;
use Modules\Chat\Http\Resources\ConversationMessageCollection;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\ConversationResource;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Services\ConversationService;

class OrderChatController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ConversationService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            ConversationCollection::make(
                $this->service->list(
                    auth('provider')->user(),
                    ChatTypeEnum::Order,
                    $request->integer('per_page', 15),
                )
            )
        );
    }

    public function store(StoreOrderChatRequest $request): JsonResponse
    {
        $order = Order::query()->findOrFail($request->validated('order_id'));

        try {
            $conversation = $this->service->open(auth('provider')->user(), $order, ChatTypeEnum::Order);
        } catch (ChatException) {
            return $this->failedResponse(
                errors: [],
                message: 'not found',
                statusCode: 404,
            );
        }

        return $this->successResponse(
            ConversationResource::make(
                $conversation->load(['lastMessage.sender', 'lastMessage.lastAttachment', 'user2', 'user1'])
            )
        );
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return $this->successResponse(
            ConversationMessageCollection::make(
                $this->service->messages(
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

        $message = $this->service->send(
            $conversation,
            auth('provider')->user(),
            ChatMessageData::fromRequest($request),
            ChatTypeEnum::Order,
        );

        return $this->successResponse(
            ConversationMessageResource::make($message->loadMissing(['sender', 'attachments']))
        );
    }
}
