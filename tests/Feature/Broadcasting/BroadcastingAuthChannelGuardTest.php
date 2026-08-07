<?php

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Modules\Marketplace\Models\Category;

/**
 * /broadcasting/auth must never 500 when the authenticated guard does not match
 * a channel's intended actor type (Admin hitting Provider-only channels).
 *
 * Regression: Provider-typed channel closures threw TypeError → HTTP 500 when
 * Admin was authenticated — exposed locally once Echo could connect over ws://.
 * See also BroadcastingMultiGuardAuthTest for Admin+Provider dual-session auth.
 *
 * phpunit.xml forces BROADCAST_CONNECTION=null (NullBroadcaster::auth is a no-op),
 * so these tests switch to reverb and re-register routes/channels.php after purge.
 */
beforeEach(function () {
    config(['broadcasting.default' => 'reverb']);
    app('Illuminate\Broadcasting\BroadcastManager')->purge();
    require base_path('routes/channels.php');
});

test('admin authorizing a provider-only category channel is denied without 500', function () {
    $admin = createOrdersAdmin();
    $category = Category::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-category.'.$category->id,
        ]);

    expect($response->status())->not->toBe(500);
    $response->assertForbidden();
});

test('admin authorizing a provider-{id} channel is denied without 500', function () {
    $admin = createOrdersAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-provider-15',
        ]);

    expect($response->status())->not->toBe(500);
    $response->assertForbidden();
});

test('registered channel callbacks reject wrong actor models without TypeError', function () {
    $admin = createOrdersAdmin();
    $provider = createWalletProvider();
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $provider->categories()->attach($category->id);

    $channels = Broadcast::connection()->getChannels();

    expect($channels)->toHaveKey('category.{category}')
        ->and($channels)->toHaveKey('provider-{id}')
        ->and($channels)->toHaveKey('user-{id}')
        ->and($channels)->toHaveKey('admin-{id}');

    expect(($channels['category.{category}'])($admin, $category))->toBeFalse()
        ->and(($channels['category.{category}'])($provider, $category))->toBeTrue()
        ->and(($channels['provider-{id}'])($admin, 15))->toBeFalse()
        ->and(($channels['provider-{id}'])($provider, (int) $provider->id))->toBeTrue()
        ->and(($channels['user-{id}'])($admin, $user->id))->toBeFalse()
        ->and(($channels['user-{id}'])($user, $user->id))->toBeTrue()
        ->and(($channels['admin-{id}'])($provider, $admin->id))->toBeFalse()
        ->and(($channels['admin-{id}'])($admin, $admin->id))->toBeTrue();
});
