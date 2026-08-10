<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Support\Actions\TicketSupport\CreateTicketSupportAction;
use Modules\Support\DTOs\StoreTicketSupportDTO;
use Modules\Support\Notifications\TicketCreatedNotification;

beforeEach(function () {
    Notification::fake();
});

test('creating a support ticket notifies all admins', function () {
    $supportAdmin = createSupportDashboardAdmin(['show supportTicket', 'edit supportTicket']);
    $otherSupportAdmin = createSupportDashboardAdmin(['show supportTicket']);
    $financeOnlyAdmin = createSupportDashboardAdmin(['show users']);

    $user = User::factory()->create();

    $ticket = app(CreateTicketSupportAction::class)->handle(new StoreTicketSupportDTO(
        userType: User::class,
        userId: $user->id,
        operationType: null,
        operationId: null,
        title: 'Need help with billing',
        message: 'Payment failed twice',
    ));

    Notification::assertSentTo($supportAdmin, TicketCreatedNotification::class, function (TicketCreatedNotification $notification) use ($ticket): bool {
        return $notification->ticket->is($ticket);
    });

    Notification::assertSentTo($otherSupportAdmin, TicketCreatedNotification::class, function (TicketCreatedNotification $notification) use ($ticket): bool {
        return $notification->ticket->is($ticket);
    });

    Notification::assertNotSentTo($financeOnlyAdmin, TicketCreatedNotification::class);
});

test('ticket created notification payload uses creation keys not status-change keys', function () {
    $admin = createSupportDashboardAdmin();
    $user = User::factory()->create();

    app(CreateTicketSupportAction::class)->handle(new StoreTicketSupportDTO(
        userType: User::class,
        userId: $user->id,
        operationType: null,
        operationId: null,
        title: 'Login issue',
        message: 'Cannot reset password',
    ));

    Notification::assertSentTo($admin, TicketCreatedNotification::class, function (TicketCreatedNotification $notification) use ($admin): bool {
        $data = $notification->toArray($admin);

        return $data['title_translated_key'] === 'support_ticket_created_title'
            && $data['body_translated_key'] === 'support_ticket_created_body'
            && isset($data['ticket_support_id']);
    });
});
