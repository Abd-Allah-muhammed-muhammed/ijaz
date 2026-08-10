<?php

use App\Models\Admin;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Modules\Support\Notifications\TicketCreatedNotification;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

function createAdminInboxNotification(Admin $admin, array $data = [], ?string $readAt = null): DatabaseNotification
{
    return DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => $data['type'] ?? TicketCreatedNotification::class,
        'notifiable_type' => $admin->getMorphClass(),
        'notifiable_id' => $admin->getKey(),
        'data' => $data['data'] ?? [
            'title_translated_key' => 'support_ticket_created_title',
            'body_translated_key' => 'support_ticket_created_body',
            'translated_attributes' => [],
            'ticket_support_id' => 1,
        ],
        'read_at' => $readAt,
    ]);
}

test('admin can fetch their unread notification count and list, mirroring the Provider inbox', function () {
    $admin = createSupportDashboardAdmin();
    $other = createSupportDashboardAdmin();

    createAdminInboxNotification($admin);
    createAdminInboxNotification($admin);
    createAdminInboxNotification($admin, readAt: now()->toDateTimeString());
    createAdminInboxNotification($other);

    $this->actingAs($admin, 'admin')
        ->getJson(route('dashboard.notifications.unread-count'))
        ->assertSuccessful()
        ->assertJsonPath('data.unread_count', 2);

    $this->actingAs($admin, 'admin')
        ->getJson(route('dashboard.notifications.index'))
        ->assertSuccessful()
        ->assertJsonPath('data.total', 3)
        ->assertJsonCount(3, 'data.items')
        ->assertJsonPath('data.items.0.title', __('support_ticket_created_title'))
        ->assertJsonPath('data.items.0.body', __('support_ticket_created_body'));
});

test('admin can mark a notification as read', function () {
    $admin = createSupportDashboardAdmin();
    $notification = createAdminInboxNotification($admin);

    $this->actingAs($admin, 'admin')
        ->postJson(route('dashboard.notifications.mark-as-read', $notification))
        ->assertSuccessful()
        ->assertJsonPath('message', 'Notification marked as read.');

    expect($notification->fresh()->read_at)->not->toBeNull();

    $this->actingAs($admin, 'admin')
        ->getJson(route('dashboard.notifications.unread-count'))
        ->assertSuccessful()
        ->assertJsonPath('data.unread_count', 0);
});
