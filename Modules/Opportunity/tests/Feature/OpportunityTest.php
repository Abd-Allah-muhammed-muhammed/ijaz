<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Infrastructure\Events\NewMessageEvent;
use Modules\Chat\Models\Conversation;
use Modules\Opportunity\Actions\Opportunity\ExpireOpportunityAction;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Http\Controllers\Api\V1\CommentController;
use Modules\Opportunity\Http\Controllers\Api\V1\OfferController;
use Modules\Opportunity\Http\Controllers\Api\V1\OpportunityChatController;
use Modules\Opportunity\Http\Controllers\Api\V1\OpportunityController;
use Modules\Opportunity\Jobs\ExpireOpportunityJob;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Notifications\OpportunityExpiredNotification;
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

// ─── offers_count visibility (viewer-scoped) ─────────────────────────────────

/**
 * @return array{author: User, offerer: User, bystander: User, opportunity: Opportunity}
 */
function createOpportunityWithThreeOffersFromDifferentUsers(): array
{
    $author = User::factory()->create();
    $offerer = User::factory()->create();
    $otherOfferer = User::factory()->create();
    $bystander = User::factory()->create();

    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);

    OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer->id,
    ]);
    OpportunityOffer::factory()->count(2)->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $otherOfferer->id,
    ]);

    expect(OpportunityOffer::query()->where('opportunity_id', $opportunity->id)->count())->toBe(3);

    return compact('author', 'offerer', 'bystander', 'opportunity');
}

test('opportunity author sees the true total offers_count', function () {
    ['author' => $author, 'opportunity' => $opportunity] = createOpportunityWithThreeOffersFromDifferentUsers();

    Sanctum::actingAs($author);

    $this->getJson(action([OpportunityController::class, 'show'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful()
        ->assertJsonPath('data.offers_count', 3);

    $list = $this->getJson(action([OpportunityController::class, 'all']))
        ->assertSuccessful()
        ->json('data.items');

    $row = collect($list)->firstWhere('id', $opportunity->id);
    expect($row['offers_count'])->toBe(3);
});

test('a non-author user with one submitted offer sees offers_count of 1, not the global total', function () {
    ['offerer' => $offerer, 'opportunity' => $opportunity] = createOpportunityWithThreeOffersFromDifferentUsers();

    Sanctum::actingAs($offerer);

    $this->getJson(action([OpportunityController::class, 'show'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful()
        ->assertJsonPath('data.offers_count', 1);

    $list = $this->getJson(action([OpportunityController::class, 'all']))
        ->assertSuccessful()
        ->json('data.items');

    $row = collect($list)->firstWhere('id', $opportunity->id);
    expect($row['offers_count'])->toBe(1);
});

test('a non-author user with no submitted offer sees offers_count of 0, even if other users have offers', function () {
    ['bystander' => $bystander, 'opportunity' => $opportunity] = createOpportunityWithThreeOffersFromDifferentUsers();

    Sanctum::actingAs($bystander);

    $this->getJson(action([OpportunityController::class, 'show'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful()
        ->assertJsonPath('data.offers_count', 0);

    $list = $this->getJson(action([OpportunityController::class, 'all']))
        ->assertSuccessful()
        ->json('data.items');

    $row = collect($list)->firstWhere('id', $opportunity->id);
    expect($row['offers_count'])->toBe(0);
});

test('unauthenticated request to opportunities list shows offers_count as 0', function () {
    ['opportunity' => $opportunity] = createOpportunityWithThreeOffersFromDifferentUsers();

    $list = $this->getJson(action([OpportunityController::class, 'all']))
        ->assertSuccessful()
        ->json('data.items');

    $row = collect($list)->firstWhere('id', $opportunity->id);
    expect($row['offers_count'])->toBe(0);

    $this->getJson(action([OpportunityController::class, 'show'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful()
        ->assertJsonPath('data.offers_count', 0);
});

test('authenticated user can create opportunity', function () {
    Carbon::setTestNow('2026-08-05 12:00:00');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson(action([OpportunityController::class, 'store']), [
        'title' => 'Backend Developer Needed',
        'description' => 'Looking for a Laravel developer for a 3 month project.',
        'budget' => 5000,
    ])->assertSuccessful()
        ->assertJsonPath('data.title', 'Backend Developer Needed')
        ->assertJsonPath('data.status.value', OpportunityStatusEnum::New->value);

    expect(Opportunity::query()->count())->toBe(1);
    expect(Carbon::parse($response->json('data.expires_at'))->equalTo(now()->addDays(Opportunity::DEFAULT_DURATION_DAYS)))->toBeTrue();

    Carbon::setTestNow();
});

test('opportunity expires_at accepts Arabic-Indic digits', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $expiresAt = now()->addDays(14)->toDateString();
    $arabicExpiresAt = strtr($expiresAt, [
        '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
        '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
    ]);

    $this->postJson(action([OpportunityController::class, 'store']), [
        'title' => 'Arabic Date Opportunity',
        'description' => 'Opportunity created with Arabic-Indic expires_at digits.',
        'expires_at' => $arabicExpiresAt,
    ])
        ->assertSuccessful()
        ->assertJsonMissingValidationErrors(['expires_at']);

    $opportunity = Opportunity::query()->where('title', 'Arabic Date Opportunity')->firstOrFail();
    expect($opportunity->expires_at->toDateString())->toBe($expiresAt);
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

test('offer price cannot be zero', function () {
    $author = User::factory()->create();
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);

    Sanctum::actingAs($offerer);

    $this->postJson(action([OfferController::class, 'store'], ['opportunity' => $opportunity->id]), [
        'price' => 0,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['price']);
});

test('opportunity author cannot submit offer on own opportunity', function () {
    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'store'], ['opportunity' => $opportunity->id]), [
        'price' => 1500,
    ])->assertForbidden()
        ->assertJsonPath('message', __('opportunity.cannot_submit_offer_on_own_opportunity'));
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

test('model not found returns 404 with message', function () {
    $this->getJson(action([OpportunityController::class, 'show'], [
        'opportunity' => '01234567-89ab-7def-0123-456789abcdef',
    ]))->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', __('opportunity.not_found'));
});

test('cannot submit second pending offer', function () {
    Notification::fake();

    $author = User::factory()->create();
    $offerer = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);

    Sanctum::actingAs($offerer);

    $this->postJson(action([OfferController::class, 'store'], ['opportunity' => $opportunity->id]), [
        'price' => 1000,
    ])->assertSuccessful();

    $this->postJson(action([OfferController::class, 'store'], ['opportunity' => $opportunity->id]), [
        'price' => 2000,
    ])->assertUnprocessable()
        ->assertJsonPath('message', __('opportunity.offer_already_submitted'));
});

test('offerer only sees own offers in list', function () {
    $author = User::factory()->create();
    $offerer1 = User::factory()->create();
    $offerer2 = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer1->id,
    ]);
    OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer2->id,
    ]);

    Sanctum::actingAs($offerer1);

    $this->getJson(action([OfferController::class, 'index'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonCount(1, 'data.items');
});

test('opportunity author sees all offers in list', function () {
    $author = User::factory()->create();
    $offerer1 = User::factory()->create();
    $offerer2 = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer1->id,
    ]);
    OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $offerer2->id,
    ]);

    Sanctum::actingAs($author);

    $this->getJson(action([OfferController::class, 'index'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful()
        ->assertJsonPath('data.total', 2)
        ->assertJsonCount(2, 'data.items');
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

test('offer creator cannot accept their own price offer', function () {
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

    Sanctum::actingAs($offerer);

    $this->postJson(action([OfferController::class, 'accept'], [
        'opportunity' => $opportunity->id,
        'offer' => $offer->id,
    ]))->assertForbidden()
        ->assertJsonPath('message', __('opportunity.unauthorized'));

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Pending);
});

test('opportunity author (or appropriate recipient) CAN accept an offer submitted by someone else', function () {
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
        ->assertJsonPath('data.status.value', OpportunityStatusEnum::OfferAccepted->value)
        ->assertJsonPath('data.accepted_offer.id', $offer->id);

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Accepted);
});

test('opportunity author cannot accept an offer they themselves authored', function () {
    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    $ownOffer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'accept'], [
        'opportunity' => $opportunity->id,
        'offer' => $ownOffer->id,
    ]))->assertForbidden()
        ->assertJsonPath('message', __('opportunity.cannot_accept_own_offer'));

    expect($ownOffer->fresh()->status)->toBe(OfferStatusEnum::Pending);
});

test('offer creator cannot reject their own price offer', function () {
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

    Sanctum::actingAs($offerer);

    $this->postJson(action([OfferController::class, 'reject'], [
        'opportunity' => $opportunity->id,
        'offer' => $offer->id,
    ]))->assertForbidden()
        ->assertJsonPath('message', __('opportunity.unauthorized'));

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Pending);
});

test('opportunity author cannot reject an offer they themselves authored', function () {
    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);
    $ownOffer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'reject'], [
        'opportunity' => $opportunity->id,
        'offer' => $ownOffer->id,
    ]))->assertForbidden()
        ->assertJsonPath('message', __('opportunity.cannot_reject_own_offer'));

    expect($ownOffer->fresh()->status)->toBe(OfferStatusEnum::Pending);
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

test('opportunity chat routes preserve their public contract', function () {
    expect(Route::has('api.v1.chats.opportunities.index'))->toBeTrue()
        ->and(route('api.v1.chats.opportunities.index'))->toBe(url('api/v1/chats/opportunities'))
        ->and(Route::has('api.v1.chats.opportunities.store'))->toBeTrue()
        ->and(route('api.v1.chats.opportunities.store'))->toBe(url('api/v1/chats/opportunities'))
        ->and(Route::has('api.v1.chats.opportunities.show'))->toBeTrue()
        ->and(route('api.v1.chats.opportunities.show', 'conversation-id'))
        ->toBe(url('api/v1/chats/opportunities/conversation-id'))
        ->and(Route::has('api.v1.chats.opportunities.send'))->toBeTrue()
        ->and(route('api.v1.chats.opportunities.send', 'conversation-id'))
        ->toBe(url('api/v1/chats/opportunities/conversation-id/send'));
});

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
        ->assertJsonStructure(['data' => ['id', 'opportunity_author', 'offer_author']]);

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

test('can send chat message successfully', function () {
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

    Sanctum::actingAs($offerer);

    $this->postJson(action([OpportunityChatController::class, 'send'], ['conversation' => $conversation->id]), [
        'content' => 'Ready to start the project.',
    ])->assertSuccessful()
        ->assertJsonPath('data.content', 'Ready to start the project.');
});

test('conversation resource has opportunity author and offer author keys', function () {
    ['author' => $author, 'opportunity' => $opportunity] = createOpportunityWithAcceptedOffer();

    Sanctum::actingAs($author);

    $this->postJson(action([OpportunityChatController::class, 'store']), [
        'opportunity_id' => $opportunity->id,
    ])->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'opportunity_author' => ['id', 'name', 'type', 'image'],
                'offer_author' => ['id', 'name', 'type', 'image'],
            ],
        ]);
});

test('can send chat message with files only', function () {
    Bus::fake();
    Event::fake([
        NewMessageEvent::class,
        ChatUpdatedEvent::class,
    ]);
    Storage::fake('public');

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

    $this->post(
        action([OpportunityChatController::class, 'send'], ['conversation' => $conversation->id]),
        ['files' => [UploadedFile::fake()->image('attachment.jpg')]],
        ['Accept' => 'application/json'],
    )->assertSuccessful();
});

test('cannot send chat message without content or files', function () {
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

    $this->postJson(action([OpportunityChatController::class, 'send'], ['conversation' => $conversation->id]), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content', 'files']);
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

// ─── Renew ───────────────────────────────────────────────────────────────────

test('owner can renew without expires at', function () {
    Carbon::setTestNow('2026-06-06 12:00:00');

    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
        'expires_at' => now()->subDay(),
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson(action([OpportunityController::class, 'renew'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful();

    $opportunity->refresh();
    $expected = now()->addDays(Opportunity::DEFAULT_DURATION_DAYS);
    expect($opportunity->expires_at->equalTo($expected))->toBeTrue();
    expect(Carbon::parse($response->json('data.expires_at'))->equalTo($expected))->toBeTrue();

    Carbon::setTestNow();
});

test('renew with null expires_at starts a fresh duration from now', function () {
    Carbon::setTestNow('2026-08-05 12:00:00');

    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
        'expires_at' => null,
        'status' => OpportunityStatusEnum::New,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson(action([OpportunityController::class, 'renew'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful();

    $opportunity->refresh();
    $expected = now()->addDays(Opportunity::DEFAULT_DURATION_DAYS);
    expect($opportunity->expires_at)->not->toBeNull();
    expect($opportunity->expires_at->equalTo($expected))->toBeTrue();
    expect(Carbon::parse($response->json('data.expires_at'))->equalTo($expected))->toBeTrue();

    Carbon::setTestNow();
});

test('owner can renew with custom expires at', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
        'expires_at' => now()->subDay(),
    ]);
    $customExpiresAt = now()->addDays(14)->startOfDay();

    Sanctum::actingAs($user);

    $response = $this->postJson(action([OpportunityController::class, 'renew'], ['opportunity' => $opportunity->id]), [
        'expires_at' => $customExpiresAt->toDateString(),
    ])->assertSuccessful();

    expect(Carbon::parse($response->json('data.expires_at'))->equalTo($customExpiresAt))->toBeTrue();
});

test('custom expires at must be future', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $this->postJson(action([OpportunityController::class, 'renew'], ['opportunity' => $opportunity->id]), [
        'expires_at' => now()->subDay()->toDateString(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['expires_at']);
});

test('non owner cannot renew opportunity', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $owner->id,
        'expires_at' => now()->subDay(),
    ]);

    Sanctum::actingAs($intruder);

    $this->postJson(action([OpportunityController::class, 'renew'], ['opportunity' => $opportunity->id]))
        ->assertForbidden();
});

test('cannot renew ended opportunity', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
        'status' => OpportunityStatusEnum::Ended,
        'expires_at' => now()->subDay(),
    ]);

    Sanctum::actingAs($user);

    $this->postJson(action([OpportunityController::class, 'renew'], ['opportunity' => $opportunity->id]))
        ->assertForbidden();
});

test('renew extends from now when already expired', function () {
    Carbon::setTestNow('2026-06-06 12:00:00');

    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
        'expires_at' => now()->subDay(),
    ]);

    Sanctum::actingAs($user);

    $this->postJson(action([OpportunityController::class, 'renew'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful();

    $opportunity->refresh();
    expect($opportunity->expires_at->equalTo(now()->addDays(Opportunity::DEFAULT_DURATION_DAYS)))->toBeTrue();
    expect($opportunity->expires_at->isFuture())->toBeTrue();

    Carbon::setTestNow();
});

test('renew extends from remaining future expiry not from now', function () {
    Carbon::setTestNow('2026-06-06 12:00:00');

    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
        'expires_at' => now()->addDays(3),
    ]);

    Sanctum::actingAs($user);

    $this->postJson(action([OpportunityController::class, 'renew'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful();

    $opportunity->refresh();
    expect($opportunity->expires_at->equalTo(now()->addDays(3 + Opportunity::DEFAULT_DURATION_DAYS)))->toBeTrue();

    Carbon::setTestNow();
});

// ─── Expiry scope ────────────────────────────────────────────────────────────

test('expired opportunity hidden from public list', function () {
    $expired = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => User::factory(),
        'expires_at' => now()->subHour(),
        'status' => OpportunityStatusEnum::New,
    ]);

    $ids = collect($this->getJson(action([OpportunityController::class, 'all']))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($ids)->not->toContain($expired->id);
});

test('active opportunity appears in public list', function () {
    $active = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => User::factory(),
        'expires_at' => now()->addDays(3),
        'status' => OpportunityStatusEnum::New,
    ]);

    $ids = collect($this->getJson(action([OpportunityController::class, 'all']))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($ids)->toContain($active->id);
});

// ─── Unauthenticated ─────────────────────────────────────────────────────────

test('unauthenticated user cannot create opportunity', function () {
    $this->postJson(action([OpportunityController::class, 'store']), [
        'title' => 'Backend Developer Needed',
        'description' => 'Looking for a Laravel developer for a 3 month project.',
    ])->assertUnauthorized();
});

test('unauthenticated user cannot submit offer', function () {
    $opportunity = Opportunity::factory()->create();

    $this->postJson(action([OfferController::class, 'store'], ['opportunity' => $opportunity->id]), [
        'price' => 1500,
        'description' => 'I can start immediately',
    ])->assertUnauthorized();
});

test('unauthenticated user cannot open chat', function () {
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::OfferAccepted,
    ]);

    $this->postJson(action([OpportunityChatController::class, 'store']), [
        'opportunity_id' => $opportunity->id,
    ])->assertUnauthorized();
});

// ─── Media upload ────────────────────────────────────────────────────────────

test('store with files returns media in response', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->post(
        action([OpportunityController::class, 'store']),
        [
            'title' => 'Opportunity with media',
            'description' => 'This opportunity includes an attachment.',
            'files' => [UploadedFile::fake()->image('test.jpg')],
        ],
        ['Accept' => 'application/json'],
    )->assertSuccessful();

    expect($response->json('data.media'))->not->toBeEmpty();
    expect($response->json('data.media.0.url'))->toContain('storage');
});

// ─── Expire system ───────────────────────────────────────────────────────────

test('expire command dispatches jobs for expired opportunities', function () {
    Queue::fake();

    $author = User::factory()->create();

    Opportunity::factory()->count(2)->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'expires_at' => now()->subHour(),
        'status' => OpportunityStatusEnum::New,
    ]);

    Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'expires_at' => now()->addDays(3),
        'status' => OpportunityStatusEnum::New,
    ]);

    $this->artisan('opportunities:expire')->assertSuccessful();

    Queue::assertPushed(ExpireOpportunityJob::class, 2);
});

test('expire command skips already terminal opportunities', function () {
    Queue::fake();

    Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => User::factory(),
        'expires_at' => now()->subHour(),
        'status' => OpportunityStatusEnum::Cancelled,
    ]);

    $this->artisan('opportunities:expire')->assertSuccessful();

    Queue::assertNotPushed(ExpireOpportunityJob::class);
});

test('expire job updates status to expired', function () {
    Notification::fake();

    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'expires_at' => now()->subHour(),
        'status' => OpportunityStatusEnum::New,
    ]);

    (new ExpireOpportunityJob($opportunity))->handle(app(ExpireOpportunityAction::class));

    expect($opportunity->fresh()->status)->toBe(OpportunityStatusEnum::Expired);
});

test('expire job sends notification to author', function () {
    Notification::fake();

    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'expires_at' => now()->subHour(),
        'status' => OpportunityStatusEnum::New,
    ]);

    (new ExpireOpportunityJob($opportunity))->handle(app(ExpireOpportunityAction::class));

    Notification::assertSentTo($author, OpportunityExpiredNotification::class);
});

test('owner can renew expired opportunity', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
        'status' => OpportunityStatusEnum::Expired,
        'expires_at' => now()->subDay(),
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson(action([OpportunityController::class, 'renew'], ['opportunity' => $opportunity->id]))
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', OpportunityStatusEnum::New->value);

    expect(Carbon::parse($response->json('data.expires_at'))->isFuture())->toBeTrue();
});
