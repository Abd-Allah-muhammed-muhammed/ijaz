<?php

use Modules\Support\Enums\TicketSupportStatusEnum;

it('has correct values', function () {
    expect(TicketSupportStatusEnum::Pending->value)->toBe('pending')
        ->and(TicketSupportStatusEnum::Open->value)->toBe('open')
        ->and(TicketSupportStatusEnum::Closed->value)->toBe('closed');
});

it('converts to array', function () {
    $array = TicketSupportStatusEnum::Pending->toArray();

    expect($array)->toBeArray()
        ->toHaveKey('label')
        ->toHaveKey('color')
        ->toHaveKey('value')
        ->and($array['value'])->toBe('pending');
});

it('returns correct color for pending', function () {
    expect(TicketSupportStatusEnum::Pending->color())->toBe('primary');
});

it('returns correct color for open', function () {
    expect(TicketSupportStatusEnum::Open->color())->toBe('success');
});

it('returns correct color for closed', function () {
    expect(TicketSupportStatusEnum::Closed->color())->toBe('danger');
});

it('converts to string', function () {
    expect(TicketSupportStatusEnum::Pending->toString())->toBeString();
});
