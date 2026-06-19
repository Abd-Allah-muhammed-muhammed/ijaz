<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\SupportTickets\TicketSupportStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\TicketSupportCollection;
use App\Http\Resources\Dashboard\TicketSupportResource;
use App\Models\TicketSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\ConversationResource;
use Modules\Chat\Services\ConversationService;

class SupportController extends Controller
{
    public function __construct(
        private readonly ConversationService $service,
    ) {}

    public function index(Request $request)
    {
        return inertia('Dashboard/Tickets/Index', [
            'rows' => function () use ($request) {
                $rows = TicketSupport::query()
                    ->latest()
                    ->with(['operation', 'user'])
                    ->paginate($request->integer('perPage', 10));

                return TicketSupportCollection::make($rows);
            },
            'prams' => fn () => $request->all() ?: [],

        ]);
    }

    public function show(TicketSupport $ticket)
    {
        $ticket->load([
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
        $request->validate([
            'status' => ['required', new Enum(TicketSupportStatusEnum::class)],
        ]);

        $ticket->update([
            'status' => $request->enum('status', TicketSupportStatusEnum::class),
        ]);

        return redirect()->back()->with('success', 'Ticket status updated successfully.');

    }

    public function openChat(TicketSupport $ticket): RedirectResponse
    {
        $admin = auth('admin')->user();

        $this->service->sendTicketSupportAsAdmin(
            $ticket,
            $admin,
            new ChatMessageData(
                content: 'مرحبا بك! معك '.$admin->name.' كيف يمكنني مساعدتك اليوم؟',
            ),
        );

        return redirect()->route('dashboard.support.tickets.show', $ticket);
    }
}
