<?php

namespace Modules\Chat\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\Actions\ListConversationsAction;
use Modules\Chat\Actions\ListMessagesAction;
use Modules\Chat\Actions\SendMessageAction;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Http\Requests\SendMessageRequest;
use Modules\Chat\Http\Resources\ConversationCollection;
use Modules\Chat\Http\Resources\ConversationMessageCollection;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Registry\ChatTypeRegistry;

#[Group('Ticket Support Chat')]
class TicketSupportChatController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ListConversationsAction $listAction,
        private readonly ListMessagesAction $listMessagesAction,
        private readonly SendMessageAction $sendAction,
        private readonly ChatTypeRegistry $registry,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $handler = $this->registry->get(ChatTypeEnum::TicketSupport);

        return $this->successResponse(
            ConversationCollection::make(
                $this->listAction->handle(
                    auth()->user(),
                    $handler,
                    $request->integer('per_page', 15),
                )
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
                    auth()->user(),
                    $request->integer('per_page', 15),
                )
            )
        );
    }

    public function send(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('send', $conversation);

        $handler = $this->registry->get(ChatTypeEnum::TicketSupport);
        $message = $this->sendAction->handle(
            $conversation,
            auth()->user(),
            ChatMessageData::fromRequest($request),
            $handler,
        );

        return $this->successResponse(
            ConversationMessageResource::make($message->loadMissing(['sender', 'attachments']))
        );
    }
}
