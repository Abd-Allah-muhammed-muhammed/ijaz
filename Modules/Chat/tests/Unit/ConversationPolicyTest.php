<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Policies\ConversationPolicy;

test('user1 can view conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    expect(Gate::forUser($user1)->allows('view', $conversation))->toBeTrue();
});

test('user2 can view conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    expect(Gate::forUser($user2)->allows('view', $conversation))->toBeTrue();
});

test('non-participant cannot view conversation', function () {
    $conversation = createMemberConversation(User::factory()->create(), User::factory()->create());
    $stranger = User::factory()->create();

    expect(Gate::forUser($stranger)->allows('view', $conversation))->toBeFalse();
});

test('user1 can send in conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    expect(Gate::forUser($user1)->allows('send', $conversation))->toBeTrue();
});

test('user2 can send in conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    expect(Gate::forUser($user2)->allows('send', $conversation))->toBeTrue();
});

test('non-participant cannot send in conversation', function () {
    $conversation = createMemberConversation(User::factory()->create(), User::factory()->create());
    $stranger = User::factory()->create();

    expect(Gate::forUser($stranger)->allows('send', $conversation))->toBeFalse();
});

test('policy works with UUID string comparison', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);
    $conversation->update([
        'user1_id' => (string) $user1->getKey(),
        'user2_id' => (string) $user2->getKey(),
    ]);

    expect(Gate::forUser($user1)->allows('view', $conversation->fresh()))->toBeTrue();
});

test('Gate resolves to Modules Chat Policies ConversationPolicy for Conversation model', function () {
    expect(Gate::getPolicyFor(Conversation::class))->toBeInstanceOf(ConversationPolicy::class);
});
