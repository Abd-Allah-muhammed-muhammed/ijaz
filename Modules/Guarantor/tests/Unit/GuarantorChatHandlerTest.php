<?php

use App\Models\User;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Chat\Support\ParticipantConversationMessenger;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Handlers\GuarantorChatHandler;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Support\GuarantorConversationMessenger;

function createGuarantorForChatHandler(?GuarantorStatusEnum $status = null): GuarantorRequest
{
    return GuarantorRequest::factory()->create([
        'status' => $status ?? GuarantorStatusEnum::InProgress,
    ]);
}

test('GuarantorChatHandler canOpen returns true for requester', function () {
    $guarantorRequest = createGuarantorForChatHandler();

    expect((new GuarantorChatHandler)->canOpen($guarantorRequest->requester, $guarantorRequest))->toBeTrue();
});

test('GuarantorChatHandler canOpen returns true for counterparty', function () {
    $guarantorRequest = createGuarantorForChatHandler();

    expect((new GuarantorChatHandler)->canOpen($guarantorRequest->counterparty, $guarantorRequest))->toBeTrue();
});

test('GuarantorChatHandler canOpen returns false for unrelated user', function () {
    $guarantorRequest = createGuarantorForChatHandler();
    $stranger = User::factory()->create();

    expect((new GuarantorChatHandler)->canOpen($stranger, $guarantorRequest))->toBeFalse();
});

test('GuarantorChatHandler operationType is GuarantorRequest::class', function () {
    expect((new GuarantorChatHandler)->operationType())->toBe(GuarantorRequest::class);
});

test('ChatTypeRegistry has Guarantor handler self-registered', function () {
    $registry = app(ChatTypeRegistry::class);

    expect($registry->get(ChatTypeEnum::Guarantor))
        ->toBeInstanceOf(GuarantorChatHandler::class)
        ->and($registry->getByOperationType(GuarantorRequest::class))
        ->toBeInstanceOf(GuarantorChatHandler::class);
});

test('GuarantorConversationMessenger extends ParticipantConversationMessenger', function () {
    expect(is_subclass_of(
        GuarantorConversationMessenger::class,
        ParticipantConversationMessenger::class,
    ))->toBeTrue();
});
