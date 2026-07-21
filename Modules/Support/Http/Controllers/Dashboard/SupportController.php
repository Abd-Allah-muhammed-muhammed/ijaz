<?php

namespace Modules\Support\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\ConversationResource;
use Modules\Chat\Services\ConversationService;
use Modules\Support\Contracts\Services\TicketSupportServiceInterface;
use Modules\Support\DTOs\UpdateTicketSupportStatusDTO;
use Modules\Support\Enums\TicketSupportStatusEnum;
use Modules\Support\Http\Resources\Dashboard\TicketSupportCollection;
use Modules\Support\Http\Resources\Dashboard\TicketSupportResource;
use Modules\Support\Models\TicketSupport;

class SupportController extends Controller
{
    public function __construct(
        private readonly TicketSupportServiceInterface $service,
        private readonly ConversationService $conversationService,
    ) {}

    public function index(Request $request)
    {
        return inertia('Dashboard/Tickets/Index', [
            'rows' => fn () => TicketSupportCollection::make(
                $this->service->indexAll($request->integer('perPage', 10)),
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
            'chat.lastMessage.lastAttachment',
            'chat.user2',
        ]);

        $messages = $ticket->chat
            ? $ticket->chat->messages()
                ->with(['attachments', 'sender'])
                ->latest()
                ->take(20)
                ->get()
                ->reverse()
            : collect();

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

        $this->conversationService->sendTicketSupportAsAdmin(
            $ticket,
            $admin,
            new ChatMessageData(
                content: 'مرحبا بك! معك '.$admin->name.' كيف يمكنني مساعدتك اليوم؟',
            ),
        );

        return redirect()->route('dashboard.support.tickets.show', $ticket);
    }
}
