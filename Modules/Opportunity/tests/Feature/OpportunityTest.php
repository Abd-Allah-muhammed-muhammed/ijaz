<?php

use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\Events\ChatUpdatedEvent;
use App\Services\Chat\Events\NewMessageEvent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Http\Controllers\V1\CommentController;
use Modules\Opportunity\Http\Controllers\V1\OfferController;
use Modules\Opportunity\Http\Controllers\V1\OpportunityChatController;
use Modules\Opportunity\Http\Controllers\V1\OpportunityController;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Notifications\OpportunityOfferAcceptedNotification;
use Modules\Opportunity\Notifications\OpportunityOfferRejectedNotification;
use Modules\Opportunity\Notifications\OpportunityOfferSubmittedNotification;

// Uses LazilyRefreshDatabase from tests/Pest.php

/**
 * @return array{author: User, offerer: User, opportunity: Opportunity, offer: OpportunityOffer}
 */
function createOpportunityWithAcceptedOffer(): array
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

// ─── Opportunity CRUD ────────────────────────────────────────────────────────

test('guest can list all public opportunities', function () {
    Opportunity::factory()->count(2)->create([
        'author_type' => User::class,
        'author_id' => User::factory(),
    ]);
    Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::Cancelled,
    ]);

    $this->getJson(action([OpportunityController::class, 'all']))
        ->assertSuccessful()
        ->assertJsonPath('data.total', 2)
        ->assertJsonStructure([
            'data' => [
                'items' => [
                    '*' => ['id', 'title', 'status', 'created_at'],
                ],
                'total',
                'per_page',
            ],
        ]);
});

test('guest can view single opportunity', function () {
    $opportunity = Opportunity::factory()->create();

    $this->getJson(action([OpportunityController::class, 'show'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $opportunity->id)
        ->assertJsonStructure([
            'data' => [
                'status' => ['value', 'label', 'color'],
            ],
        ]);
});

test('authenticated user can create opportunity', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson(action([OpportunityController::class, 'store']), [
        'title' => 'Backend Developer Needed',
        'description' => 'Looking for a Laravel developer for a 3 month project.',
        'budget' => 5000,
    ])->assertSuccessful()
        ->assertJsonPath('data.title', 'Backend Developer Needed')
        ->assertJsonPath('data.status.value', OpportunityStatusEnum::New->value);

    expect(Opportunity::query()->count())->toBe(1);
});

test('create opportunity validates required fields', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson(action([OpportunityController::class, 'store']), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'description']);
});

test('owner can update opportunity', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $this->postJson(action([OpportunityController::class, 'update'], ['opportunity' => $opportunity->id]), [
        'title' => 'Updated Title',
    ])->assertSuccessful()
        ->assertJsonPath('data.title', 'Updated Title');
});

test('non owner cannot update opportunity', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $owner->id,
    ]);

    Sanctum::actingAs($other);

    $this->postJson(action([OpportunityController::class, 'update'], ['opportunity' => $opportunity->id]), [
        'title' => 'Hacked Title',
    ])->assertForbidden();
});

test('owner can delete opportunity when status is new', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson(action([OpportunityController::class, 'destroy'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful()
        ->assertJsonPath('message', __('opportunity.deleted_successfully'));

    expect(Opportunity::query()->find($opportunity->id))->toBeNull();
});

test('owner cannot delete opportunity when status is not new', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
        'status' => OpportunityStatusEnum::OfferAccepted,
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson(action([OpportunityController::class, 'destroy'], ['opportunity' => $opportunity->id]))
        ->assertForbidden()
        ->assertJsonPath('message', __('opportunity.cannot_delete_non_new'));
});

// ─── Offers ──────────────────────────────────────────────────────────────────

test('authenticated user can submit offer', function () {
    Notification::fake();

    $author = User::factory()->create();
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);

    Sanctum::actingAs($offerer);

    $this->postJson(action([OfferController::class, 'store'], ['opportunity' => $opportunity->id]), [
        'price' => 1500,
        'description' => 'I can start immediately',
    ])->assertSuccessful()
        ->assertJsonPath('data.price', '1500.00')
        ->assertJsonPath('data.status.value', OfferStatusEnum::Pending->value);
});

test('cannot submit offer on non new opportunity', function () {
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::OfferAccepted,
    ]);

    Sanctum::actingAs($offerer);

    $this->postJson(action([OfferController::class, 'store'], ['opportunity' => $opportunity->id]), [
        'price' => 1500,
    ])->assertUnprocessable()
        ->assertJsonPath('message', __('opportunity.cannot_submit_offer_non_new'));
});

test('author can accept offer', function () {
    $author = User::factory()->create();
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    $offer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer->id,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'accept'], [
        'opportunity' => $opportunity->id,
        'offer' => $offer->id,
    ]))->assertSuccessful()
        ->assertJsonPath('data.status.value', OpportunityStatusEnum::OfferAccepted->value);
});

test('accepting offer rejects other pending offers', function () {
    $author = User::factory()->create();
    $offerer1 = User::factory()->create();
    $offerer2 = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    $firstOffer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer1->id,
    ]);
    $secondOffer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer2->id,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'accept'], [
        'opportunity' => $opportunity->id,
        'offer' => $secondOffer->id,
    ]))->assertSuccessful();

    expect($firstOffer->fresh()->status)->toBe(OfferStatusEnum::Rejected);
    expect($secondOffer->fresh()->status)->toBe(OfferStatusEnum::Accepted);
});

test('accepting offer creates conversation', function () {
    $author = User::factory()->create();
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    $offer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer->id,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'accept'], [
        'opportunity' => $opportunity->id,
        'offer' => $offer->id,
    ]))->assertSuccessful();

    expect(Conversation::query()
        ->where('operation_type', Opportunity::class)
        ->where('operation_id', $opportunity->id)
        ->exists())->toBeTrue();
});

test('accepting offer updates opportunity status to offer accepted', function () {
    $author = User::factory()->create();
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    $offer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer->id,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'accept'], [
        'opportunity' => $opportunity->id,
        'offer' => $offer->id,
    ]))->assertSuccessful();

    $opportunity->refresh();
    expect($opportunity->status)->toBe(OpportunityStatusEnum::OfferAccepted);
    expect($opportunity->accepted_offer_id)->toBe($offer->id);
});

test('author can reject offer', function () {
    $author = User::factory()->create();
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    $offer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer->id,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'reject'], [
        'opportunity' => $opportunity->id,
        'offer' => $offer->id,
    ]))->assertSuccessful()
        ->assertJsonPath('message', __('opportunity.offer_rejected_successfully'));

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Rejected);
});

test('submitting offer sends notification to opportunity author', function () {
    Notification::fake();

    $author = User::factory()->create();
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);

    Sanctum::actingAs($offerer);

    $this->postJson(action([OfferController::class, 'store'], ['opportunity' => $opportunity->id]), [
        'price' => 1500,
        'description' => 'I can start immediately',
    ])->assertSuccessful();

    Notification::assertSentTo(
        $author,
        OpportunityOfferSubmittedNotification::class
    );
});

test('accepting offer sends notification to offer author', function () {
    Notification::fake();

    $author = User::factory()->create();
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    $offer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer->id,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'accept'], [
        'opportunity' => $opportunity->id,
        'offer' => $offer->id,
    ]))->assertSuccessful();

    Notification::assertSentTo(
        $offerer,
        OpportunityOfferAcceptedNotification::class
    );
});

test('rejecting offer sends notification to offer author', function () {
    Notification::fake();

    $author = User::factory()->create();
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    $offer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer->id,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'reject'], [
        'opportunity' => $opportunity->id,
        'offer' => $offer->id,
    ]))->assertSuccessful();

    Notification::assertSentTo(
        $offerer,
        OpportunityOfferRejectedNotification::class
    );
});

test('non author cannot accept offer', function () {
    $author = User::factory()->create();
    $intruder = User::factory()->create();
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    $offer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer->id,
    ]);

    Sanctum::actingAs($intruder);

    $this->postJson(action([OfferController::class, 'accept'], [
        'opportunity' => $opportunity->id,
        'offer' => $offer->id,
    ]))->assertForbidden()
        ->assertJsonPath('message', __('opportunity.unauthorized'));
});

// ─── Comments ────────────────────────────────────────────────────────────────

test('guest can list comments', function () {
    $opportunity = Opportunity::factory()->create();
    OpportunityComment::factory()->count(2)->create([
        'opportunity_id' => $opportunity->id,
    ]);

    $this->getJson(action([CommentController::class, 'index'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful()
        ->assertJsonPath('data.total', 2);
});

test('authenticated user can add comment', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson(action([CommentController::class, 'store'], ['opportunity' => $opportunity->id]), [
        'body' => 'I am interested in this opportunity.',
    ])->assertSuccessful()
        ->assertJsonPath('data.body', 'I am interested in this opportunity.');
});

test('comment author can delete comment', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create();
    $comment = OpportunityComment::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson(action([CommentController::class, 'destroy'], [
        'opportunity' => $opportunity->id,
        'comment' => $comment->id,
    ]))->assertSuccessful()
        ->assertJsonPath('message', __('opportunity.comment_deleted_successfully'));
});

test('non author cannot delete comment', function () {
    $author = User::factory()->create();
    $other = User::factory()->create();
    $opportunity = Opportunity::factory()->create();
    $comment = OpportunityComment::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);

    Sanctum::actingAs($other);

    $this->deleteJson(action([CommentController::class, 'destroy'], [
        'opportunity' => $opportunity->id,
        'comment' => $comment->id,
    ]))->assertForbidden()
        ->assertJsonPath('message', __('opportunity.unauthorized'));
});

// ─── Policy edge cases ─────────────────────────────────────────────────────────

test('author cannot accept offer when opportunity status is not new', function () {
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
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'accept'], [
        'opportunity' => $opportunity->id,
        'offer' => $offer->id,
    ]))->assertUnprocessable()
        ->assertJsonPath('message', __('opportunity.cannot_accept_offer'));
});

test('cannot reject offer that does not belong to opportunity', function () {
    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    $otherOpportunity = Opportunity::factory()->create();
    $offer = OpportunityOffer::factory()->create([
        'opportunity_id' => $otherOpportunity->id,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'reject'], [
        'opportunity' => $opportunity->id,
        'offer' => $offer->id,
    ]))->assertForbidden()
        ->assertJsonPath('message', __('opportunity.offer_not_belong_to_opportunity'));
});

test('cannot delete comment from wrong opportunity', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create();
    $otherOpportunity = Opportunity::factory()->create();
    $comment = OpportunityComment::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson(action([CommentController::class, 'destroy'], [
        'opportunity' => $otherOpportunity->id,
        'comment' => $comment->id,
    ]))->assertForbidden()
        ->assertJsonPath('message', __('opportunity.unauthorized'));
});

test('non owner cannot delete opportunity media', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $owner->id,
    ]);
    $media = $opportunity->addMedia(UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
        ->toMediaCollection('files');

    Sanctum::actingAs($intruder);

    $this->deleteJson(action([OpportunityController::class, 'deleteMedia'], [
        'opportunity' => $opportunity->id,
        'media' => $media->uuid,
    ]))->assertForbidden()
        ->assertJsonPath('message', __('opportunity.unauthorized'));
});

test('owner cannot delete media when opportunity status is not new', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $owner->id,
        'status' => OpportunityStatusEnum::OfferAccepted,
    ]);
    $media = $opportunity->addMedia(UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
        ->toMediaCollection('files');

    Sanctum::actingAs($owner);

    $this->deleteJson(action([OpportunityController::class, 'deleteMedia'], [
        'opportunity' => $opportunity->id,
        'media' => $media->uuid,
    ]))->assertForbidden()
        ->assertJsonPath('message', __('opportunity.cannot_delete_media_non_new'));
});

// ─── Translation & Response shape ────────────────────────────────────────────

test('error messages are translated', function () {
    app()->setLocale('en');

    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
        'status' => OpportunityStatusEnum::OfferAccepted,
    ]);

    Sanctum::actingAs($user);

    $this->deleteJson(action([OpportunityController::class, 'destroy'], ['opportunity' => $opportunity->id]))
        ->assertForbidden()
        ->assertJsonPath('message', 'You can only delete opportunities with status New');
});

test('response contains status as array', function () {
    $opportunity = Opportunity::factory()->create();

    $this->getJson(action([OpportunityController::class, 'show'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'status' => ['value', 'label', 'color'],
            ],
        ])
        ->assertJsonPath('data.status.value', OpportunityStatusEnum::New->value)
        ->assertJsonPath('data.status.label', __('opportunity.status.new'));
});

// ─── Opportunity Chat ────────────────────────────────────────────────────────

test('actor can list opportunity chats', function () {
    ['author' => $author, 'offerer' => $offerer, 'opportunity' => $opportunity] = createOpportunityWithAcceptedOffer();

    Conversation::query()->create([
        'operation_type' => Opportunity::class,
        'operation_id' => $opportunity->id,
        'user1_id' => $author->id,
        'user1_type' => User::class,
        'user2_id' => $offerer->id,
        'user2_type' => User::class,
    ]);

    Sanctum::actingAs($author);

    $this->getJson(action([OpportunityChatController::class, 'index']))
        ->assertSuccessful()
        ->assertJsonPath('data.paginate.total', 1);
});

test('non actor cannot open chat', function () {
    ['opportunity' => $opportunity] = createOpportunityWithAcceptedOffer();
    $intruder = User::factory()->create();

    Sanctum::actingAs($intruder);

    $this->postJson(action([OpportunityChatController::class, 'store']), [
        'opportunity_id' => $opportunity->id,
    ])->assertForbidden()
        ->assertJsonPath('message', __('opportunity.chat_unauthorized'));
});

test('cannot open chat when no accepted offer', function () {
    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::OfferAccepted,
        'accepted_offer_id' => null,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OpportunityChatController::class, 'store']), [
        'opportunity_id' => $opportunity->id,
    ])->assertUnprocessable()
        ->assertJsonPath('message', __('opportunity.no_accepted_offer'));
});

test('cannot open chat when status is new', function () {
    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::New,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OpportunityChatController::class, 'store']), [
        'opportunity_id' => $opportunity->id,
    ])->assertForbidden()
        ->assertJsonPath('message', __('opportunity.chat_unauthorized'));
});

test('open chat creates conversation', function () {
    ['author' => $author, 'opportunity' => $opportunity] = createOpportunityWithAcceptedOffer();

    Sanctum::actingAs($author);

    $this->postJson(action([OpportunityChatController::class, 'store']), [
        'opportunity_id' => $opportunity->id,
    ])->assertSuccessful()
        ->assertJsonStructure(['data' => ['id', 'user1', 'user2']]);

    expect(Conversation::query()
        ->where('operation_type', Opportunity::class)
        ->where('operation_id', $opportunity->id)
        ->exists())->toBeTrue();
});

test('duplicate store returns same conversation', function () {
    ['author' => $author, 'opportunity' => $opportunity] = createOpportunityWithAcceptedOffer();

    Sanctum::actingAs($author);

    $first = $this->postJson(action([OpportunityChatController::class, 'store']), [
        'opportunity_id' => $opportunity->id,
    ])->assertSuccessful()->json('data.id');

    $second = $this->postJson(action([OpportunityChatController::class, 'store']), [
        'opportunity_id' => $opportunity->id,
    ])->assertSuccessful()->json('data.id');

    expect($first)->toBe($second);
    expect(Conversation::query()
        ->where('operation_type', Opportunity::class)
        ->where('operation_id', $opportunity->id)
        ->count())->toBe(1);
});

test('actor can view messages', function () {
    ['author' => $author, 'offerer' => $offerer, 'opportunity' => $opportunity] = createOpportunityWithAcceptedOffer();

    $conversation = Conversation::query()->create([
        'operation_type' => Opportunity::class,
        'operation_id' => $opportunity->id,
        'user1_id' => $author->id,
        'user1_type' => User::class,
        'user2_id' => $offerer->id,
        'user2_type' => User::class,
    ]);

    Sanctum::actingAs($author);

    $this->getJson(action([OpportunityChatController::class, 'show'], ['conversation' => $conversation->id]))
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['items', 'paginate']]);
});

test('actor can send message', function () {
    Bus::fake();
    Event::fake([
        NewMessageEvent::class,
        ChatUpdatedEvent::class,
    ]);

    ['author' => $author, 'offerer' => $offerer, 'opportunity' => $opportunity] = createOpportunityWithAcceptedOffer();

    $conversation = Conversation::query()->create([
        'operation_type' => Opportunity::class,
        'operation_id' => $opportunity->id,
        'user1_id' => $author->id,
        'user1_type' => User::class,
        'user2_id' => $offerer->id,
        'user2_type' => User::class,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OpportunityChatController::class, 'send'], ['conversation' => $conversation->id]), [
        'content' => 'Hello, when can we start?',
    ])->assertSuccessful()
        ->assertJsonPath('data.content', 'Hello, when can we start?');
});

test('non actor cannot send message', function () {
    ['opportunity' => $opportunity] = createOpportunityWithAcceptedOffer();
    $intruder = User::factory()->create();

    $conversation = Conversation::query()->create([
        'operation_type' => Opportunity::class,
        'operation_id' => $opportunity->id,
        'user1_id' => User::factory()->create()->id,
        'user1_type' => User::class,
        'user2_id' => User::factory()->create()->id,
        'user2_type' => User::class,
    ]);

    Sanctum::actingAs($intruder);

    $this->postJson(action([OpportunityChatController::class, 'send'], ['conversation' => $conversation->id]), [
        'content' => 'Unauthorized message',
    ])->assertForbidden()
        ->assertJsonPath('message', __('opportunity.chat_unauthorized'));
});
