<?php

use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Infrastructure\Events\NewMessageEvent;
use Modules\Chat\Models\ConversationMessage;
use Modules\Support\Http\Controllers\Dashboard\SupportChatController;
use Modules\Support\Http\Controllers\Dashboard\SupportController;

/**
 * Frontend-only Tickets → ConversationContent migration must not change
 * SupportChatController / SupportController contracts that the shared
 * component (and Inertia page) rely on.
 */
test('admin can still view and send support ticket messages after the frontend migration', function () {
    withoutSupportDashboardLocaleMiddleware();
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    $admin = createSupportDashboardAdmin([
        'show supportTicket',
        'edit supportTicket',
    ]);

    ['ticket' => $ticket, 'conversation' => $conversation, 'user' => $user] = createTicketSupportConversation();

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $user->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user->getKey(),
        'content' => 'Hello from ticket user',
    ]);

    // ConversationContent loads history via SupportChatController@show.
    $this->actingAs($admin, 'admin')
        ->getJson(action([SupportChatController::class, 'show'], ['ticket' => $ticket]))
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['items', 'paginate']])
        ->assertJsonFragment(['content' => 'Hello from ticket user']);

    // ConversationContent posts via SupportChatController@send (JSON).
    $this->actingAs($admin, 'admin')
        ->postJson(action([SupportChatController::class, 'send'], ['ticket' => $ticket]), [
            'content' => 'Admin support reply after ConversationContent migration',
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.content', 'Admin support reply after ConversationContent migration')
        ->assertJsonPath('data.sender.name', $admin->name);

    expect(
        ConversationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->whereMorphedTo('sender', $admin)
            ->where('content', 'Admin support reply after ConversationContent migration')
            ->exists()
    )->toBeTrue();

    // Inertia show shell still seeds chat for controlled ConversationContent.
    $this->actingAs($admin, 'admin')
        ->get(action([SupportController::class, 'show'], ['ticket' => $ticket]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Tickets/Show')
            ->where('chat.id', $conversation->id)
            ->has('row.id')
        );
});

test('ticket chat send always returns JSON and never redirects, matching Admin Orders', function () {
    withoutSupportDashboardLocaleMiddleware();
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    $admin = createSupportDashboardAdmin([
        'show supportTicket',
        'edit supportTicket',
    ]);

    ['ticket' => $ticket] = createTicketSupportConversation();

    // Simulate a non-Accept:application/json POST (the old dual-path redirected here).
    // ConversationContent's axios path must still get a JSON body, never a 302 to Show.
    $this->actingAs($admin, 'admin')
        ->post(
            action([SupportChatController::class, 'send'], ['ticket' => $ticket]),
            ['content' => 'JSON-only send path'],
            ['Accept' => 'text/html,application/xhtml+xml'],
        )
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.content', 'JSON-only send path')
        ->assertHeader('content-type', 'application/json')
        ->assertHeaderMissing('Location');
});
