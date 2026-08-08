<?php

namespace Modules\Support\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Http\Requests\ListConversationMessagesRequest;
use Modules\Chat\Http\Requests\SendSupportMessageRequest;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\Dashboard\ConversationMessageCollection;
use Modules\Support\Models\TicketSupport;
use Modules\Support\Services\TicketSupportChatService;

class SupportChatController extends Controller implements HasMiddleware
{
    use HasApiResponse;

    public function __construct(
        private readonly TicketSupportChatService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show supportTicket', only: ['show']),
            new Middleware('permission:edit supportTicket', only: ['send', 'typing']),
        ];
    }

    public function show(ListConversationMessagesRequest $request, TicketSupport $ticket): JsonResponse
    {
        $conversation = $ticket->chat;

        if (! $conversation) {
            return $this->failedMessageResponse('No conversation found for this ticket');
        }

        return $this->successResponse(
            ConversationMessageCollection::make(
                $this->service->listMessages(
                    $conversation,
                    auth('admin')->user(),
                    $request->integer('per_page', 20),
                    $request->searchTerm(),
                )
            )
        );
    }

    public function send(
        SendSupportMessageRequest $request,
        TicketSupport $ticket,
    ): JsonResponse {
        $message = $this->service->sendAsAdmin(
            $ticket,
            auth('admin')->user(),
            ChatMessageData::fromRequest($request),
        );

        // Always JSON — ConversationContent (and any XHR client) posts via axios.
        // The previous dual-path redirect-to-Show was leftover from the old Inertia
        // useForm send and caused a full page visit for non-JSON Accept headers.
        return $this->successResponse(
            ConversationMessageResource::make($message)
        );
    }

    public function typing(TicketSupport $ticket): JsonResponse
    {
        $this->service->typingAsAdmin($ticket, auth('admin')->user());

        return $this->successResponse([]);
    }
}
