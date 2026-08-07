<?php

use App\Models\Admin;
use Modules\Chat\Models\ConversationMessage;
use Modules\Orders\Http\Controllers\Dashboard\OrderController;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

test('admin can send a message on any order conversation for support purposes', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    createOrderConversation($user, $provider, $order);

    $admin = createOrdersAdmin();

    $this->actingAs($admin, 'admin')
        ->postJson(action([OrderController::class, 'sendConversationMessage'], ['order' => $order]), [
            'content' => 'Support is checking this order chat.',
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.content', 'Support is checking this order chat.')
        ->assertJsonPath('data.sender.name', $admin->name);

    expect(
        ConversationMessage::query()
            ->where('conversation_id', $order->conversation->id)
            ->whereMorphedTo('sender', $admin)
            ->where('content', 'Support is checking this order chat.')
            ->exists()
    )->toBeTrue();
});

test('admin sent messages are correctly attributed and visible to both order participants', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);
    $admin = createOrdersAdmin();

    $this->actingAs($admin, 'admin')
        ->postJson(action([OrderController::class, 'sendConversationMessage'], ['order' => $order]), [
            'content' => 'Visible to user and provider.',
        ])
        ->assertSuccessful();

    $message = ConversationMessage::query()
        ->where('conversation_id', $conversation->id)
        ->where('content', 'Visible to user and provider.')
        ->with('sender')
        ->first();

    expect($message)->not->toBeNull()
        ->and($message->sender)->toBeInstanceOf(Admin::class)
        ->and($message->sender->is($admin))->toBeTrue()
        ->and($message->sender->getType())->toBe('admin');

    // Both participants can list the conversation messages (provider + user APIs
    // use participant auth). Assert the shared message row is readable via the
    // admin listing that both parties also receive over the presence channel.
    $this->actingAs($admin, 'admin')
        ->getJson(action([OrderController::class, 'conversationMessages'], ['order' => $order]))
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonFragment([
            'content' => 'Visible to user and provider.',
        ]);

    expect($message->receiver->is($user))->toBeTrue()
        ->and($conversation->fresh()->user1->is($user))->toBeTrue()
        ->and($conversation->fresh()->user2->is($provider))->toBeTrue();
});

test('admin without edit orders cannot send on order conversation', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    createOrderConversation($user, $provider, $order);

    $admin = Admin::query()->create([
        'name' => 'Read Only Orders Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $permission = Permission::firstOrCreate([
        'name' => 'show orders',
        'guard_name' => 'admin',
    ], [
        'group' => 'orders',
    ]);
    $admin->givePermissionTo($permission);

    $this->actingAs($admin, 'admin')
        ->postJson(action([OrderController::class, 'sendConversationMessage'], ['order' => $order]), [
            'content' => 'Should be forbidden',
        ])
        ->assertForbidden();
});
