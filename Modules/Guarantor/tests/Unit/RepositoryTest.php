<?php

use App\Models\User;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\StatusHistoryRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

test('can create guarantor request via repository', function () {
    $repository = app(GuarantorRepositoryInterface::class);
    $requester = User::factory()->create();
    $counterparty = User::factory()->create();

    $guarantorRequest = $repository->create([
        'type' => GuarantorTypeEnum::Individual,
        'title' => 'Test request',
        'description' => 'Test description',
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'counterparty_type' => User::class,
        'counterparty_id' => $counterparty->getKey(),
        'amount' => 1000,
        'fees' => 10,
        'status' => GuarantorStatusEnum::New,
    ]);

    expect($guarantorRequest)->toBeInstanceOf(GuarantorRequest::class)
        ->and($guarantorRequest->title)->toBe('Test request')
        ->and($guarantorRequest->exists)->toBeTrue();
});

test('can update guarantor request via repository', function () {
    $guarantorRequest = GuarantorRequest::factory()->create(['title' => 'Original']);
    $repository = app(GuarantorRepositoryInterface::class);

    $updated = $repository->update($guarantorRequest, ['title' => 'Updated']);

    expect($updated->title)->toBe('Updated')
        ->and($updated->id)->toBe($guarantorRequest->id);
});

test('findById loads all relations', function () {
    $guarantorRequest = GuarantorRequest::factory()->create();
    GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create();

    app(StatusHistoryRepositoryInterface::class)->log(
        $guarantorRequest,
        $guarantorRequest->requester,
        GuarantorStatusEnum::New->value,
        GuarantorStatusEnum::Approved->value,
    );

    $found = app(GuarantorRepositoryInterface::class)->findById($guarantorRequest->id);

    expect($found->relationLoaded('requester'))->toBeTrue()
        ->and($found->relationLoaded('counterparty'))->toBeTrue()
        ->and($found->relationLoaded('installments'))->toBeTrue()
        ->and($found->relationLoaded('companyDetail'))->toBeTrue()
        ->and($found->relationLoaded('statusHistories'))->toBeTrue()
        ->and($found->relationLoaded('media'))->toBeTrue();
});

test('listForActor returns only actor requests', function () {
    $actor = User::factory()->create();
    $other = User::factory()->create();

    $asRequester = GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $actor->getKey(),
    ]);

    $asCounterparty = GuarantorRequest::factory()->create([
        'counterparty_type' => User::class,
        'counterparty_id' => $actor->getKey(),
    ]);

    GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $other->getKey(),
        'counterparty_type' => User::class,
        'counterparty_id' => User::factory(),
    ]);

    $results = app(GuarantorRepositoryInterface::class)
        ->listForActor($actor, perPage: 50);

    expect($results->total())->toBe(2)
        ->and($results->pluck('id')->all())->toContain($asRequester->id, $asCounterparty->id);
});

test('listByRequester returns only requester requests', function () {
    $requester = User::factory()->create();
    $other = User::factory()->create();

    $owned = GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
    ]);

    GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $other->getKey(),
    ]);

    $results = app(GuarantorRepositoryInterface::class)
        ->listByRequester($requester, perPage: 50);

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($owned->id);
});

test('listByCounterparty returns only counterparty requests', function () {
    $counterparty = User::factory()->create();
    $other = User::factory()->create();

    $assigned = GuarantorRequest::factory()->create([
        'counterparty_type' => User::class,
        'counterparty_id' => $counterparty->getKey(),
    ]);

    GuarantorRequest::factory()->create([
        'counterparty_type' => User::class,
        'counterparty_id' => $other->getKey(),
    ]);

    $results = app(GuarantorRepositoryInterface::class)
        ->listByCounterparty($counterparty, perPage: 50);

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($assigned->id);
});

test('getNextPendingForRequest returns lowest order pending installment', function () {
    $guarantorRequest = GuarantorRequest::factory()->create();

    GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'status' => InstallmentStatusEnum::Paid,
        'paid_at' => now(),
    ]);

    $next = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 2,
        'status' => InstallmentStatusEnum::Pending,
    ]);

    GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 3,
        'status' => InstallmentStatusEnum::Pending,
    ]);

    $result = app(InstallmentRepositoryInterface::class)
        ->getNextPendingForRequest($guarantorRequest);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($next->id)
        ->and($result->order)->toBe(2);
});

test('getOverdue returns only past due pending installments', function () {
    $request = GuarantorRequest::factory()->create();

    $overdue = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->overdue()->create([
        'order' => 1,
    ]);
    GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 2,
        'due_date' => now()->addDays(10),
        'status' => InstallmentStatusEnum::Pending,
    ]);
    GuarantorInstallment::factory()->for($request, 'guarantorRequest')->overdue()->paid()->create([
        'order' => 3,
    ]);

    $results = app(InstallmentRepositoryInterface::class)
        ->getOverdue()
        ->collect();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($overdue->id);
});

test('status history can be logged via repository', function () {
    $guarantorRequest = GuarantorRequest::factory()->create();
    $actor = $guarantorRequest->requester;

    $history = app(StatusHistoryRepositoryInterface::class)->log(
        $guarantorRequest,
        $actor,
        GuarantorStatusEnum::New->value,
        GuarantorStatusEnum::Approved->value,
        reason: 'Approved by counterparty',
        notes: 'All good',
    );

    expect($history->guarantor_request_id)->toBe($guarantorRequest->id)
        ->and($history->actor_id)->toBe($actor->getKey())
        ->and($history->actor_type)->toBe(User::class)
        ->and($history->from_status)->toBe(GuarantorStatusEnum::New->value)
        ->and($history->to_status)->toBe(GuarantorStatusEnum::Approved->value)
        ->and($history->reason)->toBe('Approved by counterparty')
        ->and($history->notes)->toBe('All good');
});
