<?php

use App\Enums\SupportTickets\TicketSupportStatusEnum;
use App\Http\Controllers\Api\V1\TicketSupportController;
use App\Models\GuaranteeRequest;
use App\Models\Order;
use App\Models\TicketSupport;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns 401 for unauthenticated user', function () {
    $this->getJson(action([TicketSupportController::class, 'index']))
        ->assertUnauthorized();
});

it('allows authenticated user to list their tickets', function () {
    Sanctum::actingAs($this->user);

    $order = Order::factory()->create(['user_id' => $this->user->id]);

    TicketSupport::factory()->count(3)->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
    ]);

    $otherUser = User::factory()->create();
    $otherOrder = Order::factory()->create(['user_id' => $otherUser->id]);
    TicketSupport::factory()->count(2)->create([
        'user_type' => User::class,
        'user_id' => $otherUser->id,
        'operation_type' => Order::class,
        'operation_id' => $otherOrder->id,
    ]);

    $this->getJson(action([TicketSupportController::class, 'index']))
        ->assertOk()
        ->assertJsonCount(3, 'data.data')
        ->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'message',
                        'status',
                        'user_id',
                        'user_type',
                        'operation_id',
                        'operation_type',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta',
                'links',
            ],
        ]);
});

it('allows authenticated user to create ticket for order', function () {
    Sanctum::actingAs($this->user);

    $order = Order::factory()->create(['user_id' => $this->user->id]);

    $ticketData = [
        'operation_type' => 'order',
        'operation_id' => $order->id,
        'title' => 'Test Support Ticket',
        'message' => 'I need help with my order',
    ];

    $this->postJson(action([TicketSupportController::class, 'store']), $ticketData)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'message',
                'status',
                'user_id',
                'user_type',
                'operation_id',
                'operation_type',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJson([
            'data' => [
                'title' => 'Test Support Ticket',
                'message' => 'I need help with my order',
                'status' => [
                    'value' => 'pending',
                    'color' => 'primary',
                ],
            ],
        ]);

    $this->assertDatabaseHas('ticket_supports', [
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'title' => 'Test Support Ticket',
        'message' => 'I need help with my order',
        'status' => TicketSupportStatusEnum::Pending->value,
    ]);
});

it('allows authenticated user to create ticket for guarantee request', function () {
    Sanctum::actingAs($this->user);

    $guaranteeRequest = GuaranteeRequest::factory()->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);

    $ticketData = [
        'operation_type' => 'guarantee_request',
        'operation_id' => $guaranteeRequest->id,
        'title' => 'Guarantee Issue',
        'message' => 'I need help with my guarantee request',
    ];

    $this->postJson(action([TicketSupportController::class, 'store']), $ticketData)
        ->assertOk()
        ->assertJson([
            'data' => [
                'title' => 'Guarantee Issue',
                'message' => 'I need help with my guarantee request',
            ],
        ]);

    $this->assertDatabaseHas('ticket_supports', [
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'operation_type' => GuaranteeRequest::class,
        'operation_id' => $guaranteeRequest->id,
        'title' => 'Guarantee Issue',
    ]);
});

it('requires valid operation type for ticket creation', function () {
    Sanctum::actingAs($this->user);

    $ticketData = [
        'operation_type' => 'invalid_type',
        'operation_id' => 1,
        'title' => 'Test Ticket',
        'message' => 'Test message',
    ];

    $this->postJson(action([TicketSupportController::class, 'store']), $ticketData)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['operation_type']);
});

it('requires all fields for ticket creation', function () {
    Sanctum::actingAs($this->user);

    $this->postJson(action([TicketSupportController::class, 'store']), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'operation_type',
            'operation_id',
            'title',
            'message',
        ]);
});

it('allows authenticated user to view their ticket', function () {
    Sanctum::actingAs($this->user);

    $order = Order::factory()->create(['user_id' => $this->user->id]);

    $ticket = TicketSupport::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'title' => 'Test Ticket',
        'message' => 'Test message',
        'status' => TicketSupportStatusEnum::Pending,
    ]);

    $this->getJson(action([TicketSupportController::class, 'show'], $ticket))
        ->assertOk()
        ->assertJson([
            'data' => [
                'id' => $ticket->id,
                'title' => 'Test Ticket',
                'message' => 'Test message',
            ],
        ]);
});

it('prevents user from viewing another users ticket', function () {
    Sanctum::actingAs($this->user);

    $otherUser = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $otherUser->id]);

    $ticket = TicketSupport::create([
        'user_type' => User::class,
        'user_id' => $otherUser->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'title' => 'Other User Ticket',
        'message' => 'Test message',
        'status' => TicketSupportStatusEnum::Pending,
    ]);

    $this->getJson(action([TicketSupportController::class, 'show'], $ticket))
        ->assertForbidden()
        ->assertJson([
            'message' => 'forbidden !!',
        ]);
});

it('allows authenticated user to delete their pending ticket', function () {
    Sanctum::actingAs($this->user);

    $order = Order::factory()->create(['user_id' => $this->user->id]);

    $ticket = TicketSupport::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'title' => 'Test Ticket',
        'message' => 'Test message',
        'status' => TicketSupportStatusEnum::Pending,
    ]);

    $this->deleteJson(action([TicketSupportController::class, 'destroy'], $ticket))
        ->assertOk()
        ->assertJson([
            'message' => 'data deleted successfully',
        ]);

    $this->assertDatabaseMissing('ticket_supports', [
        'id' => $ticket->id,
    ]);
});

it('prevents user from deleting non pending ticket', function () {
    Sanctum::actingAs($this->user);

    $order = Order::factory()->create(['user_id' => $this->user->id]);

    $ticket = TicketSupport::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'title' => 'Test Ticket',
        'message' => 'Test message',
        'status' => TicketSupportStatusEnum::Open,
    ]);

    $this->deleteJson(action([TicketSupportController::class, 'destroy'], $ticket))
        ->assertUnprocessable()
        ->assertJson([
            'message' => 'you can not delete this ticket',
        ]);

    $this->assertDatabaseHas('ticket_supports', [
        'id' => $ticket->id,
    ]);
});

it('prevents user from deleting another users ticket', function () {
    Sanctum::actingAs($this->user);

    $otherUser = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $otherUser->id]);

    $ticket = TicketSupport::create([
        'user_type' => User::class,
        'user_id' => $otherUser->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'title' => 'Other User Ticket',
        'message' => 'Test message',
        'status' => TicketSupportStatusEnum::Pending,
    ]);

    $this->deleteJson(action([TicketSupportController::class, 'destroy'], $ticket))
        ->assertForbidden()
        ->assertJson([
            'message' => 'forbidden !!',
        ]);

    $this->assertDatabaseHas('ticket_supports', [
        'id' => $ticket->id,
    ]);
});

it('paginates tickets', function () {
    Sanctum::actingAs($this->user);

    $order = Order::factory()->create(['user_id' => $this->user->id]);

    TicketSupport::factory()->count(15)->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
    ]);

    $this->getJson(action([TicketSupportController::class, 'index'], ['per_page' => 10]))
        ->assertOk()
        ->assertJsonCount(10, 'data.data')
        ->assertJsonStructure([
            'data' => [
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ],
        ]);
});

it('orders tickets by latest first', function () {
    Sanctum::actingAs($this->user);

    $order = Order::factory()->create(['user_id' => $this->user->id]);

    $oldTicket = TicketSupport::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'title' => 'Old Ticket',
        'message' => 'Old message',
        'status' => TicketSupportStatusEnum::Pending,
        'created_at' => now()->subDays(2),
    ]);

    $newTicket = TicketSupport::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'title' => 'New Ticket',
        'message' => 'New message',
        'status' => TicketSupportStatusEnum::Pending,
        'created_at' => now(),
    ]);

    $response = $this->getJson(action([TicketSupportController::class, 'index']));

    $response->assertOk();

    $tickets = $response->json('data.data');
    expect($tickets[0]['id'])->toBe($newTicket->id)
        ->and($tickets[1]['id'])->toBe($oldTicket->id);
});
