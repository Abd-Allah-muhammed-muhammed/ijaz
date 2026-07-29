<?php

use App\Models\User;
use Modules\Support\Enums\TicketSupportStatusEnum;
use Modules\Support\Http\Controllers\Dashboard\SupportChatController;
use Modules\Support\Http\Controllers\Dashboard\SupportController;

test('admin without supportTicket permission cannot access dashboard ticket routes', function () {
    withoutSupportDashboardLocaleMiddleware();

    $admin = createSupportDashboardAdmin(['show users']);
    $user = User::factory()->create();
    $ticket = createTestTicketSupport($user);

    $this->actingAs($admin, 'admin')
        ->get(action([SupportController::class, 'index']))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get(action([SupportController::class, 'show'], ['ticket' => $ticket->id]))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->put(action([SupportController::class, 'updateStatus'], ['ticket' => $ticket->id]), [
            'status' => TicketSupportStatusEnum::Open->value,
        ])
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->post(action([SupportController::class, 'openChat'], ['ticket' => $ticket->id]))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->post(action([SupportChatController::class, 'send'], ['ticket' => $ticket->id]), [
            'content' => 'Hello',
        ])
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(TicketSupportStatusEnum::Pending);
});
