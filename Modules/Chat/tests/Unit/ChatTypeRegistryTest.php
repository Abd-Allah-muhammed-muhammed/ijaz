<?php

use App\Models\Order;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Handlers\MemberChatHandler;
use Modules\Chat\Handlers\OrderChatHandler;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Support\Handlers\TicketSupportChatHandler;
use Modules\Support\Models\TicketSupport;

test('registry can register and retrieve handler by type', function () {
    $registry = new ChatTypeRegistry;
    $handler = new MemberChatHandler;
    $registry->register(ChatTypeEnum::Member, $handler);

    expect($registry->get(ChatTypeEnum::Member))->toBe($handler);
});

test('registry throws exception for unregistered type', function () {
    $registry = new ChatTypeRegistry;

    $registry->get(ChatTypeEnum::Member);
})->throws(RuntimeException::class);

test('registry can get handler by operation type', function () {
    $registry = app(ChatTypeRegistry::class);

    expect($registry->getByOperationType(Order::class))
        ->toBeInstanceOf(OrderChatHandler::class);
});

test('registry returns null for unknown operation type', function () {
    $registry = app(ChatTypeRegistry::class);

    expect($registry->getByOperationType('App\\Models\\Unknown'))->toBeNull();
});

test('registry returns all handlers including self-registered domain types', function () {
    $registry = app(ChatTypeRegistry::class);

    expect($registry->all())->toHaveCount(5)
        ->and($registry->all())->toHaveKeys([
            ChatTypeEnum::Member->value,
            ChatTypeEnum::Order->value,
            ChatTypeEnum::TicketSupport->value,
            ChatTypeEnum::Opportunity->value,
            ChatTypeEnum::Guarantor->value,
        ]);
});

test('MemberChatHandler has null operationType', function () {
    expect((new MemberChatHandler)->operationType())->toBeNull();
});

test('OrderChatHandler operationType is Order::class', function () {
    expect((new OrderChatHandler)->operationType())->toBe(Order::class);
});

test('TicketSupportChatHandler operationType is TicketSupport::class', function () {
    expect((new TicketSupportChatHandler)->operationType())->toBe(TicketSupport::class);
});
