<?php

use App\Models\User;
use Modules\Support\Enums\TicketSupportStatusEnum;
use Modules\Support\Http\Controllers\Dashboard\SupportController;
use Modules\Support\Models\TicketSupport;

test('admin can search tickets by title', function (): void {
    withoutSupportDashboardLocaleMiddleware();

    $admin = createSupportDashboardAdmin(['show supportTicket']);
    $user = User::factory()->create();

    $matchingTicket = TicketSupport::query()->create([
        'user_type' => User::class,
        'user_id' => $user->id,
        'title' => 'UniqueBillingIssueTicket',
        'message' => 'Billing problem details',
        'status' => TicketSupportStatusEnum::Pending,
    ]);

    TicketSupport::query()->create([
        'user_type' => User::class,
        'user_id' => $user->id,
        'title' => 'UnrelatedSupportTopic',
        'message' => 'Something else',
        'status' => TicketSupportStatusEnum::Pending,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([SupportController::class, 'index'], ['search' => 'UniqueBillingIssueTicket']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Tickets/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matchingTicket->id)
        );
});
