<?php

namespace Modules\Support\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rules\Enum;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\ConversationResource;
use Modules\Support\Contracts\Services\TicketSupportServiceInterface;
use Modules\Support\DTOs\UpdateTicketSupportStatusDTO;
use Modules\Support\Enums\TicketSupportStatusEnum;
use Modules\Support\Http\Resources\Dashboard\TicketSupportCollection;
use Modules\Support\Http\Resources\Dashboard\TicketSupportResource;
use Modules\Support\Models\TicketSupport;
use Modules\Support\Services\TicketSupportChatService;

class SupportController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly TicketSupportServiceInterface $service,
        private readonly TicketSupportChatService $chatService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show supportTicket', only: ['index', 'show']),
            new Middleware('permission:edit supportTicket', only: ['updateStatus', 'openChat']),
        ];
    }

    public function index(Request $request)
    {
        return inertia('Dashboard/Tickets/Index', [
            'rows' => fn () => TicketSupportCollection::make(
                $this->service->indexAll($request),
            ),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    public function show(TicketSupport $ticket)
    {
        $ticket = $this->service->show($ticket, [
            'operation',
            'user',
            'chat.lastMessage.sender',
            'chat.lastMessage.media',
            'chat.user2',
        ]);

        $messages = $this->chatService->listRecentMessages($ticket);

        return inertia('Dashboard/Tickets/Show', [
            'row' => fn () => TicketSupportResource::make($ticket),
            'chat' => fn () => $ticket->chat
                ? ConversationResource::make($ticket->chat)
                : null,
            'chatMessages' => fn () => $ticket->chat
                ? ConversationMessageResource::collection($messages)->resolve()
                : [],
        ]);
    }

    public function updateStatus(TicketSupport $ticket, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', new Enum(TicketSupportStatusEnum::class)],
        ]);

        $this->service->updateStatus(
            $ticket,
            UpdateTicketSupportStatusDTO::fromValidated($validated),
        );

        return redirect()->back()->with('success', 'Ticket status updated successfully.');
    }

    public function openChat(TicketSupport $ticket): RedirectResponse
    {
        $admin = auth('admin')->user();

        $this->chatService->sendAsAdmin(
            $ticket,
            $admin,
            new ChatMessageData(
                content: 'مرحبا بك! معك '.$admin->name.' كيف يمكنني مساعدتك اليوم؟',
            ),
        );

        return redirect()->route('dashboard.support.tickets.show', $ticket);
    }
}
