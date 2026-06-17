<?php

use Modules\Guarantor\Enums\GuarantorStatusEnum;

test('requester can end from in_progress', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::InProgress,
        GuarantorStatusEnum::Ended,
        'requester'
    ))->toBeTrue();
});

test('requester can end from overdue', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::Overdue,
        GuarantorStatusEnum::Ended,
        'requester'
    ))->toBeTrue();
});

test('requester cannot accept from approved_by_admin', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::ApprovedByAdmin,
        GuarantorStatusEnum::Accepted,
        'requester'
    ))->toBeFalse();
});

test('requester cannot reject from approved_by_admin', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::ApprovedByAdmin,
        GuarantorStatusEnum::Rejected,
        'requester'
    ))->toBeFalse();
});

test('requester cannot end from pending_admin', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::PendingAdmin,
        GuarantorStatusEnum::Ended,
        'requester'
    ))->toBeFalse();
});

test('counterparty can accept from approved_by_admin', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::ApprovedByAdmin,
        GuarantorStatusEnum::Accepted,
        'counterparty'
    ))->toBeTrue();
});

test('counterparty can reject from approved_by_admin', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::ApprovedByAdmin,
        GuarantorStatusEnum::Rejected,
        'counterparty'
    ))->toBeTrue();
});

test('counterparty cannot accept from pending_admin', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::PendingAdmin,
        GuarantorStatusEnum::Accepted,
        'counterparty'
    ))->toBeFalse();
});

test('counterparty can end from in_progress', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::InProgress,
        GuarantorStatusEnum::Ended,
        'counterparty'
    ))->toBeTrue();
});

test('counterparty cannot transition from pending_admin', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::PendingAdmin,
        GuarantorStatusEnum::Rejected,
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
        GuarantorStatusEnum::RejectedByAdmin,
        GuarantorStatusEnum::Rejected,
        GuarantorStatusEnum::Ended,
        GuarantorStatusEnum::Cancelled,
        GuarantorStatusEnum::Refunded,
    ];

    foreach ($terminalStatuses as $status) {
        expect(GuarantorStatusEnum::isAllowed($status, GuarantorStatusEnum::PendingAdmin, 'requester'))->toBeFalse();
        expect(GuarantorStatusEnum::isAllowed($status, GuarantorStatusEnum::Accepted, 'counterparty'))->toBeFalse();
    }
});

test('cannot set same status twice for non-admin', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::Accepted,
        GuarantorStatusEnum::Accepted,
        'counterparty'
    ))->toBeFalse();
});
