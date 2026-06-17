<?php

use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;
use Modules\Guarantor\Http\Requests\UpdateGuarantorStatusRequest;

test('GuarantorData can be constructed', function () {
    $data = new GuarantorData(
        title: 'Test title',
        description: 'Test description',
        amount: 1500.50,
        counterparty_phone: '+966501234567',
        project_type: 'construction',
    );

    expect($data->title)->toBe('Test title')
        ->and($data->description)->toBe('Test description')
        ->and($data->amount)->toBe(1500.50)
        ->and($data->counterparty_phone)->toBe('+966501234567')
        ->and($data->project_type)->toBe('construction');
});

test('CompanyDetailData can be constructed', function () {
    $data = new CompanyDetailData(
        company_name: 'Acme Corp',
        commercial_register: 'CR-123456',
        region_id: 1,
        city_id: 10,
        authorized_name: 'John Doe',
        authorized_id_number: '1234567890',
        authorization_type: 'power_of_attorney',
        requester_account_holder: 'Requester Name',
        requester_iban: 'SA1234567890123456789012',
        counterparty_account_holder: 'Counterparty Name',
        counterparty_iban: 'SA9876543210987654321098',
    );

    expect($data->company_name)->toBe('Acme Corp')
        ->and($data->commercial_register)->toBe('CR-123456')
        ->and($data->region_id)->toBe(1)
        ->and($data->city_id)->toBe(10)
        ->and($data->authorization_type)->toBe('power_of_attorney')
        ->and($data->counterparty_iban)->toBe('SA9876543210987654321098');
});

test('InstallmentData can be constructed from array', function () {
    $data = InstallmentData::fromArray([
        'order' => 2,
        'amount' => 2500.75,
        'due_date' => '2026-08-15',
    ]);

    expect($data->order)->toBe(2)
        ->and($data->amount)->toBe(2500.75)
        ->and($data->due_date)->toBe('2026-08-15');
});

test('InstallmentData collectionFromRequest returns array of InstallmentData', function () {
    $request = Mockery::mock(StoreCompanyGuarantorRequest::class);
    $request->shouldReceive('validated')
        ->with('installments')
        ->andReturn([
            ['order' => 1, 'amount' => 1000, 'due_date' => '2026-07-01'],
            ['order' => 2, 'amount' => 2000, 'due_date' => '2026-08-01'],
        ]);

    $result = InstallmentData::collectionFromRequest($request);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(InstallmentData::class)
        ->and($result[0]->order)->toBe(1)
        ->and($result[0]->amount)->toBe(1000.0)
        ->and($result[1]->order)->toBe(2);
});

test('UpdateGuarantorStatusData casts status to enum', function () {
    $request = Mockery::mock(UpdateGuarantorStatusRequest::class);
    $request->shouldReceive('validated')->with('status')->andReturn('approved');
    $request->shouldReceive('validated')->with('reason')->andReturn('Approved by counterparty');
    $request->shouldReceive('validated')->with('notes')->andReturn(null);

    $data = UpdateGuarantorStatusData::fromRequest($request);

    expect($data->status)->toBe(GuarantorStatusEnum::Approved)
        ->and($data->reason)->toBe('Approved by counterparty')
        ->and($data->notes)->toBeNull();
});
