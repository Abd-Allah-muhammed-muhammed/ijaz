<?php

use Modules\Chat\Http\Controllers\Provider\OrderChatController;
use Modules\Chat\Models\ConversationMessage;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

test('requesting page 2 of a conversation returns the next older page of messages, not duplicates or the same page', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    $created = [];
    for ($i = 1; $i <= 25; $i++) {
        $message = ConversationMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $provider->getKey(),
            'sender_type' => $provider::class,
            'receiver_id' => $user->getKey(),
            'receiver_type' => $user::class,
            'content' => "Message number {$i}",
            'has_attachments' => false,
        ]);
        $message->forceFill([
            'created_at' => now()->subMinutes(25 - $i),
            'updated_at' => now()->subMinutes(25 - $i),
        ])->save();
        $created[] = $message;
    }

    $page1 = $this->actingAs($provider, 'provider')
        ->getJson(action([OrderChatController::class, 'show'], [
            'conversation' => $conversation->id,
            'per_page' => 10,
            'page' => 1,
        ]))
        ->assertSuccessful()
        ->json('data');

    $page2 = $this->actingAs($provider, 'provider')
        ->getJson(action([OrderChatController::class, 'show'], [
            'conversation' => $conversation->id,
            'per_page' => 10,
            'page' => 2,
        ]))
        ->assertSuccessful()
        ->json('data');

    $page1Ids = collect($page1['items'])->pluck('id')->all();
    $page2Ids = collect($page2['items'])->pluck('id')->all();

    expect($page1['paginate']['current_page'])->toBe(1)
        ->and($page1['paginate']['has_more_pages'])->toBeTrue()
        ->and($page2['paginate']['current_page'])->toBe(2)
        ->and($page1Ids)->toHaveCount(10)
        ->and($page2Ids)->toHaveCount(10)
        ->and(array_intersect($page1Ids, $page2Ids))->toBe([])
        // Newest-first: page 1 starts with the newest message.
        ->and($page1Ids[0])->toBe($created[24]->id)
        // Page 2 continues with older messages (next offset), no wrap back to page 1.
        ->and($page2Ids[0])->toBe($created[14]->id)
        ->and($page2Ids[9])->toBe($created[5]->id);
});
