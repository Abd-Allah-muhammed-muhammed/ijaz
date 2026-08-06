<?php

namespace Modules\Chat\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\Contracts\ParticipantResolverInterface;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Http\Requests\ListConversationMessagesRequest;
use Modules\Chat\Http\Requests\SendMessageRequest;
use Modules\Chat\Http\Requests\StoreConversationRequest;
use Modules\Chat\Http\Resources\ConversationCollection;
use Modules\Chat\Http\Resources\ConversationMessageCollection;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\ConversationResource;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Services\ConversationService;

class MemberChatController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ConversationService $service,
        private readonly ParticipantResolverInterface $participants,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            ConversationCollection::make(
                $this->service->list(
                    auth('provider')->user(),
                    ChatTypeEnum::Member,
                    $request->integer('per_page', 15),
                )
            )
        );
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $receiver = $this->participants->resolveFromSocketId($request->validated('socket_id'));

        if ($receiver === null) {
            return $this->failedMessageResponse(trans('User Not Found'));
        }

        $conversation = $this->service->openMemberChat(auth('provider')->user(), $receiver);

        return $this->successResponse(
            ConversationResource::make(
                $conversation->load(['lastMessage.sender', 'lastMessage.media', 'user2', 'user1'])
            )
        );
    }

    public function show(ListConversationMessagesRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return $this->successResponse(
            ConversationMessageCollection::make(
                $this->service->messages(
                    $conversation,
                    auth('provider')->user(),
                    $request->integer('per_page', 15),
                    $request->searchTerm(),
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
            ChatTypeEnum::Member,
        );

        return $this->successResponse(
            ConversationMessageResource::make($message->loadMissing(['sender', 'media']))
        );
    }
}
