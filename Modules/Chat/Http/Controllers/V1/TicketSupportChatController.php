<?php

namespace Modules\Chat\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Http\Requests\SendMessageRequest;
use Modules\Chat\Http\Resources\ConversationCollection;
use Modules\Chat\Http\Resources\ConversationMessageCollection;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Services\ConversationService;

#[Group('Ticket Support Chat')]
class TicketSupportChatController extends Controller
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
                    ChatTypeEnum::TicketSupport,
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
            ChatTypeEnum::TicketSupport,
        );

        return $this->successResponse(
            ConversationMessageResource::make($message->loadMissing(['sender', 'attachments']))
        );
    }
}
