<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;
use Modules\Opportunity\Models\OpportunityOffer;

test('opportunity policy allows owner to update', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
    ]);

    expect(Gate::forUser($user)->allows('update', $opportunity))->toBeTrue();
});

test('opportunity policy denies non owner update', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $owner->id,
    ]);

    expect(Gate::forUser($other)->allows('update', $opportunity))->toBeFalse();
});

test('opportunity policy allows delete only when status is new', function () {
    $user = User::factory()->create();
    $newOpportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
    ]);
    $acceptedOpportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
        'status' => OpportunityStatusEnum::OfferAccepted,
    ]);

    expect(Gate::forUser($user)->allows('delete', $newOpportunity))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $acceptedOpportunity))->toBeFalse();
});

test('opportunity offer policy allows create only on new opportunities', function () {
    $user = User::factory()->create();
    $openOpportunity = Opportunity::factory()->create();
    $closedOpportunity = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::Ended,
    ]);

    expect(Gate::forUser($user)->allows('create', [OpportunityOffer::class, $openOpportunity]))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', [OpportunityOffer::class, $closedOpportunity]))->toBeFalse();
});

test('opportunity offer policy denies author from submitting on own opportunity', function () {
    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);

    expect(Gate::forUser($author)->allows('create', [OpportunityOffer::class, $opportunity]))->toBeFalse();
});

test('opportunity comment policy allows author to delete own comment', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create();
    $comment = OpportunityComment::factory()->create([
        'opportunity_id' => $opportunity->id,
        'author_type' => User::class,
        'author_id' => $user->id,
    ]);

    expect(Gate::forUser($user)->allows('delete', [$comment, $opportunity]))->toBeTrue();
});

test('opportunity policy denies remove media when file belongs to another opportunity', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $user->id,
    ]);
    $otherOpportunity = Opportunity::factory()->create();
    $media = $otherOpportunity->addMedia(UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
        ->toMediaCollection('files');

    expect(Gate::forUser($user)->allows('removeMedia', [$opportunity, $media]))->toBeFalse();
});

test('opportunity policy allows chat for author when offer accepted', function () {
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

    expect(Gate::forUser($author)->allows('chat', $opportunity))->toBeTrue();
});

test('opportunity policy denies chat when status is new', function () {
    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
    ]);

    expect(Gate::forUser($author)->allows('chat', $opportunity))->toBeFalse();
});