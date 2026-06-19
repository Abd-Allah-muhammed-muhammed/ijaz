<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

function policyGuarantorRequest(array $attributes = []): GuarantorRequest
{
    return GuarantorRequest::factory()->create($attributes);
}

test('requester can update when status is pending_admin', function () {
    $requester = User::factory()->create();
    $guarantorRequest = policyGuarantorRequest([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'status' => GuarantorStatusEnum::PendingAdmin,
    ]);

    expect(Gate::forUser($requester)->allows('update', $guarantorRequest))->toBeTrue();
});

test('requester cannot update when status is not pending_admin', function () {
    $requester = User::factory()->create();
    $guarantorRequest = policyGuarantorRequest([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'status' => GuarantorStatusEnum::Accepted,
    ]);

    expect(Gate::forUser($requester)->allows('update', $guarantorRequest))->toBeFalse();
});

test('counterparty cannot update', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::PendingAdmin]);
    $counterparty = $guarantorRequest->counterparty;

    expect(Gate::forUser($counterparty)->allows('update', $guarantorRequest))->toBeFalse();
});

test('requester can delete when status is pending_admin', function () {
    $requester = User::factory()->create();
    $guarantorRequest = policyGuarantorRequest([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'status' => GuarantorStatusEnum::PendingAdmin,
    ]);

    expect(Gate::forUser($requester)->allows('delete', $guarantorRequest))->toBeTrue();
});

test('requester cannot delete when status is not pending_admin', function () {
    $requester = User::factory()->create();
    $guarantorRequest = policyGuarantorRequest([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'status' => GuarantorStatusEnum::Accepted,
    ]);

    expect(Gate::forUser($requester)->allows('delete', $guarantorRequest))->toBeFalse();
});

test('counterparty can updateStatus', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::PendingAdmin]);
    $counterparty = $guarantorRequest->counterparty;

    expect(Gate::forUser($counterparty)->allows('updateStatus', $guarantorRequest))->toBeTrue();
});

test('stranger cannot updateStatus', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::PendingAdmin]);
    $stranger = User::factory()->create();

    expect(Gate::forUser($stranger)->allows('updateStatus', $guarantorRequest))->toBeFalse();
});

test('counterparty can pay when status is accepted', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::Accepted]);
    $counterparty = $guarantorRequest->counterparty;

    expect(Gate::forUser($counterparty)->allows('pay', $guarantorRequest))->toBeTrue();
});

test('counterparty cannot pay when status is approved_by_admin', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::ApprovedByAdmin]);
    $counterparty = $guarantorRequest->counterparty;

    expect(Gate::forUser($counterparty)->allows('pay', $guarantorRequest))->toBeFalse();
});

test('requester cannot pay', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::Accepted]);
    $requester = $guarantorRequest->requester;

    expect(Gate::forUser($requester)->allows('pay', $guarantorRequest))->toBeFalse();
});

test('counterparty cannot pay when status is not accepted', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::PendingAdmin]);
    $counterparty = $guarantorRequest->counterparty;

    expect(Gate::forUser($counterparty)->allows('pay', $guarantorRequest))->toBeFalse();
});

test('requester cannot deleteMedia when status is not pending_admin', function () {
    $requester = User::factory()->create();
    $guarantorRequest = policyGuarantorRequest([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'status' => GuarantorStatusEnum::Accepted,
    ]);

    expect(Gate::forUser($requester)->allows('deleteMedia', $guarantorRequest))->toBeFalse();
});

test('parties cannot chat when status is pending_admin', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::PendingAdmin]);

    expect(Gate::forUser($guarantorRequest->requester)->allows('chat', $guarantorRequest))->toBeFalse()
        ->and(Gate::forUser($guarantorRequest->counterparty)->allows('chat', $guarantorRequest))->toBeFalse();
});

test('parties cannot chat when status is approved_by_admin', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::ApprovedByAdmin]);

    expect(Gate::forUser($guarantorRequest->requester)->allows('chat', $guarantorRequest))->toBeFalse()
        ->and(Gate::forUser($guarantorRequest->counterparty)->allows('chat', $guarantorRequest))->toBeFalse();
});

test('requester can end when status is in_progress', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::InProgress]);
    $requester = $guarantorRequest->requester;

    expect(Gate::forUser($requester)->allows('end', $guarantorRequest))->toBeTrue();
});

test('counterparty can end when status is in_progress', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::InProgress]);
    $counterparty = $guarantorRequest->counterparty;

    expect(Gate::forUser($counterparty)->allows('end', $guarantorRequest))->toBeTrue();
});

test('both parties can chat when status is in_progress', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::InProgress]);

    expect(Gate::forUser($guarantorRequest->requester)->allows('chat', $guarantorRequest))->toBeTrue()
        ->and(Gate::forUser($guarantorRequest->counterparty)->allows('chat', $guarantorRequest))->toBeTrue();
});

test('parties cannot chat when status is accepted', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::Accepted]);

    expect(Gate::forUser($guarantorRequest->requester)->allows('chat', $guarantorRequest))->toBeFalse()
        ->and(Gate::forUser($guarantorRequest->counterparty)->allows('chat', $guarantorRequest))->toBeFalse();
});

test('stranger cannot chat', function () {
    $guarantorRequest = policyGuarantorRequest(['status' => GuarantorStatusEnum::InProgress]);
    $stranger = User::factory()->create();

    expect(Gate::forUser($stranger)->allows('chat', $guarantorRequest))->toBeFalse();
});

test('requester can view own request', function () {
    $guarantorRequest = policyGuarantorRequest();

    expect(Gate::forUser($guarantorRequest->requester)->allows('view', $guarantorRequest))->toBeTrue();
});

test('stranger cannot view request', function () {
    $guarantorRequest = policyGuarantorRequest();
    $stranger = User::factory()->create();

    expect(Gate::forUser($stranger)->allows('view', $guarantorRequest))->toBeFalse();
});

test('counterparty can pay installment when status is accepted', function () {
    $counterparty = User::factory()->create();
    $requester = User::factory()->create();
    $guarantorRequest = policyGuarantorRequest([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'counterparty_type' => User::class,
        'counterparty_id' => $counterparty->getKey(),
        'status' => GuarantorStatusEnum::Accepted,
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create();

    expect(Gate::forUser($counterparty)->allows('pay', $installment))->toBeTrue();
});

test('counterparty cannot pay installment when status is pending_admin', function () {
    $counterparty = User::factory()->create();
    $requester = User::factory()->create();
    $guarantorRequest = policyGuarantorRequest([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'counterparty_type' => User::class,
        'counterparty_id' => $counterparty->getKey(),
        'status' => GuarantorStatusEnum::PendingAdmin,
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create();

    expect(Gate::forUser($counterparty)->allows('pay', $installment))->toBeFalse();
});

test('counterparty can pay installment', function () {
    $counterparty = User::factory()->create();
    $requester = User::factory()->create();
    $guarantorRequest = policyGuarantorRequest([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'counterparty_type' => User::class,
        'counterparty_id' => $counterparty->getKey(),
        'status' => GuarantorStatusEnum::InProgress,
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create();

    expect(Gate::forUser($counterparty)->allows('pay', $installment))->toBeTrue();
});

test('requester cannot pay installment', function () {
    $counterparty = User::factory()->create();
    $requester = User::factory()->create();
    $guarantorRequest = policyGuarantorRequest([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'counterparty_type' => User::class,
        'counterparty_id' => $counterparty->getKey(),
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create();

    expect(Gate::forUser($requester)->allows('pay', $installment))->toBeFalse();
});

function policyConversation(User $user1, User $user2): Conversation
{
    $guarantorRequest = policyGuarantorRequest();

    return Conversation::query()->create([
        'user1_id' => $user1->getKey(),
        'user1_type' => User::class,
        'user2_id' => $user2->getKey(),
        'user2_type' => User::class,
        'operation_type' => GuarantorRequest::class,
        'operation_id' => $guarantorRequest->id,
    ]);
}

test('user1 can view conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = policyConversation($user1, $user2);

    expect(Gate::forUser($user1)->allows('view', $conversation))->toBeTrue();
});

test('user2 can send message', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = policyConversation($user1, $user2);

    expect(Gate::forUser($user2)->allows('send', $conversation))->toBeTrue();
});

test('stranger cannot view conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $stranger = User::factory()->create();
    $conversation = policyConversation($user1, $user2);

    expect(Gate::forUser($stranger)->allows('view', $conversation))->toBeFalse();
});
