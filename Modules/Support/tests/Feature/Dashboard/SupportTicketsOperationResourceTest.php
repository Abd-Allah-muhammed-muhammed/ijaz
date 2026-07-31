<?php

use App\Models\User;
use Modules\Orders\Models\Order;
use Modules\Support\Enums\TicketSupportStatusEnum;
use Modules\Support\Http\Controllers\Dashboard\SupportController;
use Modules\Support\Models\TicketSupport;

test('support tickets dashboard index loads with operation resource resolved correctly', function (): void {
    withoutSupportDashboardLocaleMiddleware();

    $admin = createSupportDashboardAdmin(['show supportTicket']);
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $ticket = TicketSupport::query()->create([
        'user_type' => User::class,
        'user_id' => $user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'title' => 'Order Linked Support Ticket',
        'message' => 'Need help with this order',
        'status' => TicketSupportStatusEnum::Pending,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([SupportController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Tickets/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $ticket->id)
            ->where('rows.data.0.operation.id', $order->id)
            ->where('rows.data.0.operation.type', 'Order')
            ->where('rows.data.0.operation.data.id', $order->id)
            ->has('rows.data.0.operation.show_url')
        );
});
