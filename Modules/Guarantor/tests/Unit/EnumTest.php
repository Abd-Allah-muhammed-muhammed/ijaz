<?php

use Modules\Guarantor\Enums\GuarantorStatusEnum;

test('requester can cancel from new', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::New,
        GuarantorStatusEnum::Cancelled,
        'requester'
    ))->toBeTrue();
});

test('requester can cancel from approved', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::Approved,
        GuarantorStatusEnum::Cancelled,
        'requester'
    ))->toBeTrue();
});

test('requester can end from in_progress', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::InProgress,
        GuarantorStatusEnum::Ended,
        'requester'
    ))->toBeTrue();
});

test('requester cannot approve', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::New,
        GuarantorStatusEnum::Approved,
        'requester'
    ))->toBeFalse();
});

test('counterparty can approve from new', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::New,
        GuarantorStatusEnum::Approved,
        'counterparty'
    ))->toBeTrue();
});

test('counterparty can reject from new', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::New,
        GuarantorStatusEnum::Rejected,
        'counterparty'
    ))->toBeTrue();
});

test('counterparty can end from in_progress', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::InProgress,
        GuarantorStatusEnum::Ended,
        'counterparty'
    ))->toBeTrue();
});

test('counterparty cannot cancel from new', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::New,
        GuarantorStatusEnum::Cancelled,
        'counterparty'
    ))->toBeFalse();
});

test('admin can do any transition', function () {
    foreach (GuarantorStatusEnum::cases() as $old) {
        foreach (GuarantorStatusEnum::cases() as $new) {
            expect(GuarantorStatusEnum::isAllowed($old, $new, 'admin'))->toBeTrue();
        }
    }
});

test('no transition from terminal status for non-admin', function () {
    $terminalStatuses = [
        GuarantorStatusEnum::Ended,
        GuarantorStatusEnum::Rejected,
        GuarantorStatusEnum::Cancelled,
        GuarantorStatusEnum::Refunded,
    ];

    foreach ($terminalStatuses as $status) {
        expect(GuarantorStatusEnum::isAllowed($status, GuarantorStatusEnum::New, 'requester'))->toBeFalse();
        expect(GuarantorStatusEnum::isAllowed($status, GuarantorStatusEnum::Approved, 'counterparty'))->toBeFalse();
    }
});
