<?php

namespace Modules\Chat\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\TicketSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Http\Requests\SendSupportMessageRequest;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\Dashboard\ConversationMessageCollection;
use Modules\Chat\Services\ConversationService;

class SupportChatController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ConversationService $service,
    ) {}

    public function show(Request $request, TicketSupport $ticketSupport): JsonResponse
    {
        $conversation = $ticketSupport->chat;

        if (! $conversation) {
            return $this->failedMessageResponse('No conversation found for this ticket');
        }

        return $this->successResponse(
            ConversationMessageCollection::make(
                $this->service->messages(
                    $conversation,
                    auth('admin')->user(),
                    $request->integer('per_page', 20),
                )
            )
        );
    }

    public function send(
        SendSupportMessageRequest $request,
        TicketSupport $ticket,
    ): JsonResponse|RedirectResponse {
        $message = $this->service->sendTicketSupportAsAdmin(
            $ticket,
            auth('admin')->user(),
            ChatMessageData::fromRequest($request),
        );

        if ($request->expectsJson()) {
            return $this->successResponse(
                ConversationMessageResource::make($message)
            );
        }

        return redirect()->route('dashboard.support.tickets.show', $ticket);
    }
}
