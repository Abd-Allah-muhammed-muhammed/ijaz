<?php

use App\Models\User;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Chat\Support\ParticipantConversationMessenger;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Handlers\OpportunityChatHandler;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Support\OpportunityConversationMessenger;

/**
 * @return array{author: User, offerer: User, opportunity: Opportunity}
 */
function createOpportunityForChatHandler(): array
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

    return compact('author', 'offerer', 'opportunity');
}

test('OpportunityChatHandler canOpen returns true for opportunity author', function () {
    ['author' => $author, 'opportunity' => $opportunity] = createOpportunityForChatHandler();

    expect((new OpportunityChatHandler)->canOpen($author, $opportunity))->toBeTrue();
});

test('OpportunityChatHandler canOpen returns false for unrelated user', function () {
    ['opportunity' => $opportunity] = createOpportunityForChatHandler();
    $stranger = User::factory()->create();

    expect((new OpportunityChatHandler)->canOpen($stranger, $opportunity))->toBeFalse();
});

test('OpportunityChatHandler operationType is Opportunity::class', function () {
    expect((new OpportunityChatHandler)->operationType())->toBe(Opportunity::class);
});

test('ChatTypeRegistry has Opportunity handler self-registered', function () {
    $registry = app(ChatTypeRegistry::class);

    expect($registry->get(ChatTypeEnum::Opportunity))
        ->toBeInstanceOf(OpportunityChatHandler::class)
        ->and($registry->getByOperationType(Opportunity::class))
        ->toBeInstanceOf(OpportunityChatHandler::class);
});

test('OpportunityConversationMessenger extends ParticipantConversationMessenger', function () {
    expect(is_subclass_of(
        OpportunityConversationMessenger::class,
        ParticipantConversationMessenger::class,
    ))->toBeTrue();
});
