<?php

namespace Modules\Chat\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Http\Requests\SendMessageRequest;
use Modules\Chat\Http\Requests\StoreConversationRequest;
use Modules\Chat\Http\Resources\ConversationCollection;
use Modules\Chat\Http\Resources\ConversationMessageCollection;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\ConversationResource;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Services\ConversationService;

#[Group('Member Chat')]
class MemberChatController extends Controller
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
                    auth()->user(),
                    ChatTypeEnum::Member,
                    $request->integer('per_page', 15),
                )
            )
        );
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $receiver = $this->resolveReceiverFromSocketId($request->validated('socket_id'));

        if ($receiver === null) {
            return $this->failedMessageResponse(trans('User Not Found'));
        }

        $conversation = $this->service->openMemberChat(auth()->user(), $receiver);

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
                    auth()->user(),
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
            auth()->user(),
            ChatMessageData::fromRequest($request),
            ChatTypeEnum::Member,
        );

        return $this->successResponse(
            ConversationMessageResource::make($message->loadMissing(['sender', 'attachments']))
        );
    }

    public function chat(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return $this->successResponse(
            ConversationResource::make(
                $conversation->load(['user1', 'user2', 'lastMessage'])
            )
        );
    }

    private function resolveReceiverFromSocketId(string $socketId): ?Model
    {
        [$type, $id] = explode('-', $socketId, 2);

        return match ($type) {
            'user' => User::query()->find($id),
            'provider' => Provider::query()->find($id),
            default => null,
        };
    }
}
