<?php

use Modules\Marketplace\Models\Category;

/**
 * When Admin + Provider sessions coexist (local multi-tab debugging),
 * /broadcasting/auth must authorize Provider-only channels as the Provider —
 * not deny them because auth:admin,provider preferred Admin.
 *
 * phpunit.xml forces BROADCAST_CONNECTION=null (NullBroadcaster::auth is a no-op),
 * so these tests switch to reverb and re-register routes/channels.php after purge.
 */
beforeEach(function () {
    config(['broadcasting.default' => 'reverb']);
    app('Illuminate\Broadcasting\BroadcastManager')->purge();
    require base_path('routes/channels.php');
});

test('provider category channel authorizes when admin session also exists', function () {
    $admin = createOrdersAdmin();
    $provider = createWalletProvider();
    $category = Category::factory()->create();
    $provider->categories()->attach($category->id);

    $response = $this->actingAs($admin, 'admin')
        ->actingAs($provider, 'provider')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-category.'.$category->id,
        ]);

    $response->assertSuccessful();
});

test('provider private channel authorizes when admin session also exists', function () {
    $admin = createOrdersAdmin();
    $provider = createWalletProvider();

    $response = $this->actingAs($admin, 'admin')
        ->actingAs($provider, 'provider')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-provider-'.$provider->id,
        ]);

    $response->assertSuccessful();
});

test('presence-online from provider dashboard prefers provider identity when both sessions exist', function () {
    $admin = createOrdersAdmin();
    $provider = createWalletProvider();

    $response = $this->actingAs($admin, 'admin')
        ->actingAs($provider, 'provider')
        ->withHeader('Referer', 'https://ijaz.test/en/provider/dashboard/chat')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'presence-online',
        ]);

    $response->assertSuccessful();

    $channelData = json_decode((string) $response->json('channel_data'), true);

    expect($channelData)->toBeArray()
        ->and($channelData['user_id'] ?? null)->toBe($provider->getAuthIdentifierForBroadcasting());
});

test('admin-only remains denied for provider channels without a provider session', function () {
    $admin = createOrdersAdmin();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'admin')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-category.'.$category->id,
        ])
        ->assertForbidden();
});
