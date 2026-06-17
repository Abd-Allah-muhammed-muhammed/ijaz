<?php

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Http\Resources\Api\CompanyDetailResource;
use Modules\Guarantor\Http\Resources\Api\GuarantorParticipantResource;
use Modules\Guarantor\Http\Resources\Api\GuarantorResource;
use Modules\Guarantor\Http\Resources\Api\InstallmentResource;
use Modules\Guarantor\Http\Resources\Api\StatusHistoryResource;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Models\GuarantorStatusHistory;

function resourceRequest(): Request
{
    return Request::create('/');
}

test('GuarantorResource returns correct fields', function () {
    $guarantorRequest = GuarantorRequest::factory()->create();
    $guarantorRequest->load(['requester', 'counterparty']);

    $data = GuarantorResource::make($guarantorRequest)->toArray(resourceRequest());

    expect($data)->toHaveKeys([
        'id',
        'type',
        'status',
        'title',
        'description',
        'amount',
        'fees',
        'total',
        'project_type',
        'cancellation_reason',
        'overdue_at',
        'ended_at',
        'cancelled_at',
        'rejected_at',
        'refunded_at',
        'created_at',
    ])
        ->and($data)->toHaveKey('requester')
        ->and($data)->toHaveKey('counterparty');
});

test('GuarantorResource status is toArray format not raw enum', function () {
    $guarantorRequest = GuarantorRequest::factory()->create();

    $data = GuarantorResource::make($guarantorRequest)->toArray(resourceRequest());

    expect($data['status'])->toBeArray()
        ->and($data['status'])->toHaveKeys(['value', 'label', 'color'])
        ->and($data['status']['value'])->toBeString();
});

test('GuarantorResource type is toArray format not raw enum', function () {
    $guarantorRequest = GuarantorRequest::factory()->create();

    $data = GuarantorResource::make($guarantorRequest)->toArray(resourceRequest());

    expect($data['type'])->toBeArray()
        ->and($data['type'])->toHaveKeys(['value', 'label', 'color'])
        ->and($data['type']['value'])->toBeString();
});

test('InstallmentResource returns is_past_due correctly', function () {
    $overdue = GuarantorInstallment::factory()->overdue()->create();
    $pending = GuarantorInstallment::factory()->create([
        'due_date' => now()->addMonth(),
    ]);

    $overdueData = InstallmentResource::make($overdue)->toArray(resourceRequest());
    $pendingData = InstallmentResource::make($pending)->toArray(resourceRequest());

    expect($overdueData['is_past_due'])->toBeTrue()
        ->and($pendingData['is_past_due'])->toBeFalse();
});

test('StatusHistoryResource resolves from_status and to_status enums', function () {
    $user = User::factory()->create();
    $guarantorRequest = GuarantorRequest::factory()->create();

    $history = GuarantorStatusHistory::query()->create([
        'guarantor_request_id' => $guarantorRequest->id,
        'actor_type' => User::class,
        'actor_id' => $user->getKey(),
        'from_status' => GuarantorStatusEnum::PendingAdmin->value,
        'to_status' => GuarantorStatusEnum::ApprovedByAdmin->value,
    ]);

    $data = StatusHistoryResource::make($history)->toArray(resourceRequest());

    expect($data['from_status']['value'])->toBe('pending_admin')
        ->and($data['to_status']['value'])->toBe('approved_by_admin');
});

test('CompanyDetailResource returns decrypted IBAN fields', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->create();

    $detail = GuarantorCompanyDetail::query()->create([
        'guarantor_request_id' => $guarantorRequest->id,
        'company_name' => 'Test Co',
        'commercial_register' => 'CR-1',
        'authorized_name' => 'Auth',
        'authorized_id_number' => '123',
        'authorization_type' => AuthorizationTypeEnum::PowerOfAttorney,
        'requester_account_holder' => 'Holder',
        'requester_iban' => 'SA0380000000608010167519',
        'counterparty_account_holder' => 'CP Holder',
    ]);

    $data = CompanyDetailResource::make($detail->fresh())->toArray(resourceRequest());

    expect($data['requester_iban'])->toBe('SA0380000000608010167519')
        ->and($data['requester_account_holder'])->toBe('Holder');
});

test('GuarantorParticipantResource returns correct type for User', function () {
    $user = User::factory()->create();

    $data = GuarantorParticipantResource::make($user)->toArray(resourceRequest());

    expect($data['type'])->toBe('user')
        ->and($data['id'])->toBe($user->getKey());
});
