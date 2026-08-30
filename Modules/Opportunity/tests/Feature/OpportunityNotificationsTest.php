<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Http\Controllers\Api\V1\OfferController;
use Modules\Opportunity\Http\Controllers\Api\V1\OpportunityController;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Notifications\OpportunityCreatedConfirmationNotification;
use Modules\Opportunity\Notifications\OpportunityExpiredNotification;
use Modules\Opportunity\Notifications\OpportunityOfferAcceptedNotification;
use Modules\Opportunity\Notifications\OpportunityOfferRejectedNotification;
use Modules\Opportunity\Notifications\OpportunityOfferSubmittedNotification;

test('every Opportunity notification class includes screen + relevant entity id (opportunity_id/offer_id as applicable) in its Firebase data', function () {
    $opportunity = Opportunity::factory()->create();
    $offer = OpportunityOffer::factory()->create(['opportunity_id' => $opportunity->id]);
    $user = User::factory()->create(['language' => 'en']);

    $cases = [
        new OpportunityCreatedConfirmationNotification($opportunity),
        new OpportunityOfferSubmittedNotification($offer),
        new OpportunityOfferAcceptedNotification($offer),
        new OpportunityOfferRejectedNotification($offer),
        new OpportunityExpiredNotification($opportunity),
    ];

    foreach ($cases as $notification) {
        $data = $notification->toFirebase($user)->getData();

        expect($data)->toHaveKey('screen')
            ->and($data['screen'])->toBe('opportunity')
            ->and($data)->toHaveKey('opportunity_id')
            ->and($data['opportunity_id'])->not->toBeEmpty();

        if ($notification instanceof OpportunityOfferSubmittedNotification
            || $notification instanceof OpportunityOfferAcceptedNotification
            || $notification instanceof OpportunityOfferRejectedNotification) {
            expect($data)->toHaveKey('offer_id')
                ->and($data['offer_id'])->not->toBeEmpty();
        }
    }
});

test('OpportunityOfferAcceptedNotification now sends Firebase, not just database+broadcast', function () {
    $offer = OpportunityOffer::factory()->create();
    $notification = new OpportunityOfferAcceptedNotification($offer);
    $user = User::factory()->create(['language' => 'en']);
    $provider = createWalletProvider(['language' => 'en']);

    expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
        ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase']);
});

test('OpportunityOfferRejectedNotification now sends Firebase, not just database+broadcast', function () {
    $offer = OpportunityOffer::factory()->create();
    $notification = new OpportunityOfferRejectedNotification($offer);
    $user = User::factory()->create(['language' => 'en']);
    $provider = createWalletProvider(['language' => 'en']);

    expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
        ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase']);
});

test('accepting one offer notifies every OTHER pending offerer that their offer was rejected (bulk-reject), not silently', function () {
    Notification::fake();

    $author = User::factory()->create();
    $acceptedOfferer = User::factory()->create();
    $rejectedOfferer1 = User::factory()->create();
    $rejectedOfferer2 = User::factory()->create();

    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);

    $acceptedOffer = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $acceptedOfferer->id,
        'status' => OfferStatusEnum::Pending,
    ]);
    $rejectedOffer1 = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $rejectedOfferer1->id,
        'status' => OfferStatusEnum::Pending,
    ]);
    $rejectedOffer2 = OpportunityOffer::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $rejectedOfferer2->id,
        'status' => OfferStatusEnum::Pending,
    ]);

    Sanctum::actingAs($author);

    $this->postJson(action([OfferController::class, 'accept'], [
        'opportunity' => $opportunity->id,
        'offer' => $acceptedOffer->id,
    ]))->assertSuccessful();

    Notification::assertSentTo($acceptedOfferer, OpportunityOfferAcceptedNotification::class);
    Notification::assertSentTo($rejectedOfferer1, OpportunityOfferRejectedNotification::class, function (OpportunityOfferRejectedNotification $notification) use ($rejectedOffer1) {
        return $notification->offer->is($rejectedOffer1);
    });
    Notification::assertSentTo($rejectedOfferer2, OpportunityOfferRejectedNotification::class, function (OpportunityOfferRejectedNotification $notification) use ($rejectedOffer2) {
        return $notification->offer->is($rejectedOffer2);
    });
    Notification::assertNotSentTo($acceptedOfferer, OpportunityOfferRejectedNotification::class);

    expect($rejectedOffer1->fresh()->status)->toBe(OfferStatusEnum::Rejected)
        ->and($rejectedOffer2->fresh()->status)->toBe(OfferStatusEnum::Rejected);
});

test('creating an Opportunity sends a confirmation notification to the author', function () {
    Notification::fake();

    $author = User::factory()->create();
    Sanctum::actingAs($author);

    $this->postJson(action([OpportunityController::class, 'store']), [
        'title' => 'Need help with a project',
        'description' => 'Looking for someone experienced to help deliver this work.',
        'budget' => 1500,
    ])->assertSuccessful();

    Notification::assertSentTo($author, OpportunityCreatedConfirmationNotification::class, function (OpportunityCreatedConfirmationNotification $notification) {
        return $notification->opportunity->author_id === auth()->id();
    });
});

test('OpportunityOfferSubmittedNotification and OpportunityExpiredNotification now send Firebase to Provider authors too, not just User', function () {
    $offer = OpportunityOffer::factory()->create();
    $opportunity = Opportunity::factory()->create();
    $provider = createWalletProvider(['language' => 'en']);

    $submitted = new OpportunityOfferSubmittedNotification($offer);
    $expired = new OpportunityExpiredNotification($opportunity);

    expect($submitted->via($provider))->toBe(['database', 'broadcast', 'firebase'])
        ->and($expired->via($provider))->toBe(['database', 'broadcast', 'firebase']);
});
