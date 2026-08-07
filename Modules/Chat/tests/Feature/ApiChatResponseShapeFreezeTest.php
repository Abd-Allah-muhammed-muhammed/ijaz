<?php

use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Http\Controllers\Api\V1\MemberChatController;
use Modules\Chat\Http\Controllers\Api\V1\OrderChatController;
use Modules\Chat\Http\Controllers\Api\V1\TicketSupportChatController;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Infrastructure\Events\NewMessageEvent;
use Modules\Chat\Models\Conversation;
use Modules\Guarantor\Http\Controllers\Api\V1\GuarantorChatController;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Http\Controllers\Api\V1\OpportunityChatController;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;

/**
 * Exact top-level `data` keys for ConversationResource open responses
 * (member / order). Includes deprecated last_massage_at — do not remove.
 */
function frozenConversationOpenKeys(): array
{
    return [
        'id',
        'user1',
        'user2',
        'last_message',
        'last_message_at',
        'last_massage_at',
        'last_message_at_iso',
    ];
}

/**
 * Exact top-level `data` keys for ConversationMessageResource send responses
 * after loadMissing(['sender', 'media']).
 */
function frozenMessageSendKeys(): array
{
    return [
        'id',
        'conversation_id',
        'content',
        'sender',
        'attachments',
        'read_at',
        'created_at',
        'created_at_iso',
    ];
}

function frozenGuarantorConversationOpenKeys(): array
{
    return [
        'id',
        'requester',
        'counterparty',
        'last_message',
        'last_message_at',
        'guarantor_request',
    ];
}

function frozenOpportunityConversationOpenKeys(): array
{
    return [
        'id',
        'opportunity_author',
        'offer_author',
        'last_message',
        'last_message_at',
        'opportunity',
    ];
}

function assertExactJsonDataKeys($response, array $expectedKeys): void
{
    $response->assertSuccessful();
    $data = $response->json('data');
    expect(array_keys($data))->toBe($expectedKeys);
}

function createOpportunityWithAcceptedOfferForShapeFreeze(): array
{
    $author = User::factory()->create();
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::OfferAccepted,
    ]);
    $offer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer->id,
        'status' => OfferStatusEnum::Accepted,
    ]);
    $opportunity->update(['accepted_offer_id' => $offer->id]);

    return compact('author', 'offerer', 'opportunity', 'offer');
}

// ─── Open ────────────────────────────────────────────────────────────────────

test('freeze: member open ConversationResource key set', function () {
    $user = User::factory()->create();
    $receiver = User::factory()->create();
    Sanctum::actingAs($user);

    assertExactJsonDataKeys(
        $this->postJson(action([MemberChatController::class, 'store']), [
            'socket_id' => $receiver->getAuthIdentifierForBroadcasting(),
        ]),
        frozenConversationOpenKeys(),
    );
});

test('freeze: order open ConversationResource key set', function () {
    ['user' => $user, 'order' => $order] = createOrderWithParticipants();
    Sanctum::actingAs($user);

    assertExactJsonDataKeys(
        $this->postJson(action([OrderChatController::class, 'store']), [
            'order_id' => $order->getKey(),
        ]),
        frozenConversationOpenKeys(),
    );
});

test('freeze: guarantor open GuarantorConversationResource key set', function () {
    $guarantorRequest = GuarantorRequest::factory()->accepted()->create();
    Sanctum::actingAs($guarantorRequest->requester);

    assertExactJsonDataKeys(
        $this->postJson(action([GuarantorChatController::class, 'store']), [
            'guarantor_request_id' => $guarantorRequest->getKey(),
        ]),
        frozenGuarantorConversationOpenKeys(),
    );
});

test('freeze: opportunity open OpportunityConversationResource key set', function () {
    ['author' => $author, 'opportunity' => $opportunity] = createOpportunityWithAcceptedOfferForShapeFreeze();
    Sanctum::actingAs($author);

    assertExactJsonDataKeys(
        $this->postJson(action([OpportunityChatController::class, 'store']), [
            'opportunity_id' => $opportunity->id,
        ]),
        frozenOpportunityConversationOpenKeys(),
    );
});

// ─── Send ────────────────────────────────────────────────────────────────────

test('freeze: member send ConversationMessageResource key set', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);
    Sanctum::actingAs($user1);

    assertExactJsonDataKeys(
        $this->postJson(action([MemberChatController::class, 'send'], ['conversation' => $conversation->id]), [
            'content' => 'Freeze member send',
        ]),
        frozenMessageSendKeys(),
    );
});

test('freeze: order send ConversationMessageResource key set', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);
    $conversation->load(['user1', 'user2']);
    Sanctum::actingAs($user);

    assertExactJsonDataKeys(
        $this->postJson(action([OrderChatController::class, 'send'], ['conversation' => $conversation->id]), [
            'content' => 'Freeze order send',
        ]),
        frozenMessageSendKeys(),
    );
});

test('freeze: ticket send ConversationMessageResource key set', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    ['user' => $user, 'conversation' => $conversation] = createTicketSupportConversation();
    $conversation->load(['user1', 'user2']);
    Sanctum::actingAs($user);

    assertExactJsonDataKeys(
        $this->postJson(action([TicketSupportChatController::class, 'send'], ['conversation' => $conversation->id]), [
            'content' => 'Freeze ticket send',
        ]),
        frozenMessageSendKeys(),
    );
});

test('freeze: guarantor send ConversationMessageResource key set', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    $guarantorRequest = GuarantorRequest::factory()->accepted()->create();
    $requester = $guarantorRequest->requester;
    $counterparty = $guarantorRequest->counterparty;

    $conversation = Conversation::query()->create([
        'user1_id' => $requester->getKey(),
        'user1_type' => $requester::class,
        'user2_id' => $counterparty->getKey(),
        'user2_type' => $counterparty::class,
        'operation_type' => GuarantorRequest::class,
        'operation_id' => $guarantorRequest->getKey(),
    ]);
    $conversation->load(['user1', 'user2']);
    Sanctum::actingAs($requester);

    assertExactJsonDataKeys(
        $this->postJson(action([GuarantorChatController::class, 'send'], ['conversation' => $conversation->id]), [
            'content' => 'Freeze guarantor send',
        ]),
        frozenMessageSendKeys(),
    );
});

test('freeze: opportunity send ConversationMessageResource key set', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    ['author' => $author, 'offerer' => $offerer, 'opportunity' => $opportunity] = createOpportunityWithAcceptedOfferForShapeFreeze();

    $conversation = Conversation::query()->create([
        'operation_type' => Opportunity::class,
        'operation_id' => $opportunity->id,
        'user1_id' => $author->id,
        'user1_type' => User::class,
        'user2_id' => $offerer->id,
        'user2_type' => User::class,
    ]);
    $conversation->load(['user1', 'user2']);
    Sanctum::actingAs($author);

    assertExactJsonDataKeys(
        $this->postJson(action([OpportunityChatController::class, 'send'], ['conversation' => $conversation->id]), [
            'content' => 'Freeze opportunity send',
        ]),
        frozenMessageSendKeys(),
    );
});
