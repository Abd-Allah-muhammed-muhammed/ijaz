<?php

use Modules\Orders\Enums\OrderStatusEnum;

test('provider can cancel from in_progress to cancelled_by_provider', function () {
    expect(OrderStatusEnum::isAllowed(
        OrderStatusEnum::InProgress,
        OrderStatusEnum::CancelledByProvider,
        'provider',
    ))->toBeTrue();
});

test('user can cancel from in_progress to cancelled_by_client', function () {
    expect(OrderStatusEnum::isAllowed(
        OrderStatusEnum::InProgress,
        OrderStatusEnum::CancelledByClient,
        'user',
    ))->toBeTrue();
});

test('provider cannot cancel from ended_by_provider', function () {
    expect(OrderStatusEnum::isAllowed(
        OrderStatusEnum::EndedByProvider,
        OrderStatusEnum::CancelledByProvider,
        'provider',
    ))->toBeFalse();
});

test('user cannot cancel from new', function () {
    expect(OrderStatusEnum::isAllowed(
        OrderStatusEnum::New,
        OrderStatusEnum::CancelledByClient,
        'user',
    ))->toBeFalse();
});

test('user cannot take the provider cancellation status', function () {
    expect(OrderStatusEnum::isAllowed(
        OrderStatusEnum::InProgress,
        OrderStatusEnum::CancelledByProvider,
        'user',
    ))->toBeFalse();
});
