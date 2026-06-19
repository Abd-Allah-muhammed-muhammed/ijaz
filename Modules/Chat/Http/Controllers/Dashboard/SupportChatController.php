<?php

namespace Modules\Chat\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\TicketSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\Actions\ListMessagesAction;
use Modules\Chat\Actions\SendMessageAction;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Http\Requests\SendSupportMessageRequest;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\Dashboard\ConversationMessageCollection;
use Modules\Chat\Registry\ChatTypeRegistry;

class SupportChatController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ListMessagesAction $listMessagesAction,
        private readonly SendMessageAction $sendAction,
        private readonly ChatTypeRegistry $registry,
    ) {}

    public function show(Request $request, TicketSupport $ticketSupport): JsonResponse
    {
        $conversation = $ticketSupport->chat;

        if (! $conversation) {
            return $this->failedMessageResponse('No conversation found for this ticket');
        }

        return $this->successResponse(
            ConversationMessageCollection::make(
                $this->listMessagesAction->handle(
                    $conversation,
                    auth('admin')->user(),
                    $request->integer('per_page', 20),
                )
            )
        );
    }

    public function send(
        SendSupportMessageRequest $request,
        TicketSupport $ticketSupport,
    ): JsonResponse {
        $conversation = $ticketSupport->chat;

        if (! $conversation) {
            return $this->failedMessageResponse('No conversation found for this ticket');
        }

        $handler = $this->registry->get(ChatTypeEnum::TicketSupport);
        $message = $this->sendAction->handle(
            $conversation,
            auth('admin')->user(),
            ChatMessageData::fromRequest($request),
            $handler,
        );

        return $this->successResponse(
            ConversationMessageResource::make($message->loadMissing(['sender', 'attachments']))
        );
    }
}
