<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\SupportTickets\TicketSupportStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\TicketSupportCollection;
use App\Http\Resources\Dashboard\TicketSupportResource;
use App\Models\TicketSupport;
use App\Services\Chat\Exceptions\ChatException;
use App\Services\Chat\Exceptions\ChatMessageException;
use App\Services\Chat\Facades\Chat;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\ConversationResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Pusher\ApiErrorException;

class SupportController extends Controller
{
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
        return inertia('Dashboard/Tickets/Show', [
            'row' => function () use ($ticket) {
                return TicketSupportResource::make($ticket->load(['operation', 'user']));
            },
            'chat' => function () use ($ticket) {
                if (! $ticket->chat) {
                    return null;
                }

                return ConversationResource::make($ticket->chat->load([
                    'lastMassage.sender',
                    'lastMassage.lastAttachment',
                    'user2',
                ]));
            },
            'chatMessages' => function () use ($ticket) {
                if (! $ticket->chat) {
                    return null;
                }
                $messages = $ticket->chat->messages()->with(['attachments', 'sender'])->latest()->take(20)->get()->reverse();

                return ConversationMessageResource::collection($messages);
            },
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

    /**
     * @throws ChatMessageException
     * @throws ChatException
     * @throws ApiErrorException
     */
    public function openChat(TicketSupport $ticket): RedirectResponse
    {
        $admin = auth('admin')->user();
        Chat::support($ticket)->replayAsAdmin(
            $admin,
            'مرحبا بك! معك '.$admin->name.' كيف يمكنني مساعدتك اليوم؟',
            [],
        );

        return redirect()->route('dashboard.support.tickets.show', $ticket);
    }

    /**
     * @throws ApiErrorException
     * @throws ChatMessageException
     * @throws ChatException
     */
    public function send(TicketSupport $ticket, Request $request): RedirectResponse
    {
        $request->validate([
            'content' => ['nullable', 'string', 'max:5000'],
            'files.*' => ['sometimes', 'nullable', 'file', 'max:10240'],
        ]);
        Chat::support($ticket)->replayAsAdmin(
            auth('admin')->user(),
            $request->input('content'),
            $request->file('files', [])
        );

        return redirect()->route('dashboard.support.tickets.show', $ticket);
    }
}
