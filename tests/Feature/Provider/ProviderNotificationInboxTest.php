<?php

use App\Models\Provider;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Wallet\Notifications\WithdrawStatusChangedNotification;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

function createProviderInboxNotification(Provider $provider, array $data = [], ?string $readAt = null): DatabaseNotification
{
    return DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => $data['type'] ?? 'App\\Notifications\\TestNotification',
        'notifiable_type' => $provider->getMorphClass(),
        'notifiable_id' => $provider->getKey(),
        'data' => $data['data'] ?? [
            'title_translated_key' => 'withdraw_status_approved_title',
            'body_translated_key' => 'withdraw_status_approved_body',
            'translated_attributes' => [],
        ],
        'read_at' => $readAt,
    ]);
}

test('provider can fetch their unread notification count and list', function () {
    $provider = createWalletProvider();
    $other = createWalletProvider();

    createProviderInboxNotification($provider);
    createProviderInboxNotification($provider);
    createProviderInboxNotification($provider, readAt: now()->toDateTimeString());
    createProviderInboxNotification($other);

    $this->actingAs($provider, 'provider')
        ->getJson(route('provider.notifications.unread-count'))
        ->assertSuccessful()
        ->assertJsonPath('data.unread_count', 2);

    $this->actingAs($provider, 'provider')
        ->getJson(route('provider.notifications.index'))
        ->assertSuccessful()
        ->assertJsonPath('data.total', 3)
        ->assertJsonCount(3, 'data.items');
});

test('provider can mark a notification as read', function () {
    $provider = createWalletProvider();
    $notification = createProviderInboxNotification($provider);

    $this->actingAs($provider, 'provider')
        ->postJson(route('provider.notifications.mark-as-read', $notification))
        ->assertSuccessful()
        ->assertJsonPath('message', 'Notification marked as read.');

    expect($notification->fresh()->read_at)->not->toBeNull();

    $this->actingAs($provider, 'provider')
        ->getJson(route('provider.notifications.unread-count'))
        ->assertSuccessful()
        ->assertJsonPath('data.unread_count', 0);
});

test('admin has no notification inbox yet — confirm this scope is Provider-only for this pass, Admin addressed separately', function () {
    expect(Route::has('provider.notifications.index'))->toBeTrue()
        ->and(Route::has('provider.notifications.unread-count'))->toBeTrue()
        ->and(Route::has('provider.notifications.mark-as-read'))->toBeTrue()
        ->and(Route::has('provider.notifications.mark-all-as-read'))->toBeTrue();

    $adminInboxRouteNames = collect(Route::getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter(fn (?string $name) => is_string($name) && str_contains($name, 'notifications') && (
            str_starts_with($name, 'dashboard.')
            || str_starts_with($name, 'admin.')
        ))
        ->values()
        ->all();

    expect($adminInboxRouteNames)->toBeEmpty();

    $this->getJson('/admin/notifications')->assertNotFound();
    $this->getJson('/dashboard/notifications')->assertNotFound();
});

test('withdraw approved StatusChangedNotification shows readable translated title and body in provider inbox', function () {
    $provider = createWalletProvider();

    createProviderInboxNotification($provider, [
        'type' => WithdrawStatusChangedNotification::class,
        'data' => [
            'title_translated_key' => 'withdraw_status_approved_title',
            'body_translated_key' => 'withdraw_status_approved_body',
            'translated_attributes' => [],
            'status' => 'approved',
            'withdraw_request_id' => (string) Str::uuid(),
        ],
    ]);

    $this->actingAs($provider, 'provider')
        ->getJson(route('provider.notifications.index'))
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.title', __('withdraw_status_approved_title'))
        ->assertJsonPath('data.items.0.body', __('withdraw_status_approved_body'))
        ->assertJsonPath('data.items.0.type', 'WithdrawStatusChangedNotification');
});

test('provider can mark all notifications as read', function () {
    $provider = createWalletProvider();
    createProviderInboxNotification($provider);
    createProviderInboxNotification($provider);

    $this->actingAs($provider, 'provider')
        ->postJson(route('provider.notifications.mark-all-as-read'))
        ->assertSuccessful()
        ->assertJsonPath('message', 'All notifications marked as read.');

    expect($provider->unreadNotifications()->count())->toBe(0);
});
