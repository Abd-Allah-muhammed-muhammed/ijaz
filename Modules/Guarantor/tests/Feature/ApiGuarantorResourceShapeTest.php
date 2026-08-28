<?php

use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Http\Resources\Api\GuarantorResource;
use Modules\Guarantor\Models\GuarantorRequest;

beforeEach(function () {
    Notification::fake();
});

/**
 * Frozen GuarantorResource key order for store-individual response loads
 * (requester, counterparty, media).
 */
function frozenIndividualGuarantorResourceKeys(): array
{
    return [
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
        'dispute_resolution',
        'requester',
        'counterparty',
        'media',
        'overdue_at',
        'ended_at',
        'cancelled_at',
        'rejected_at',
        'refunded_at',
        'created_at',
    ];
}

/**
 * Frozen GuarantorResource key order for store-company response loads
 * (requester, counterparty, installments, companyDetail.media, media).
 */
function frozenCompanyGuarantorResourceKeys(): array
{
    return [
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
        'dispute_resolution',
        'requester',
        'counterparty',
        'installments',
        'company_detail',
        'media',
        'overdue_at',
        'ended_at',
        'cancelled_at',
        'rejected_at',
        'refunded_at',
        'created_at',
    ];
}

/**
 * Frozen GuarantorResource key order after loadForShow (update/show).
 */
function frozenShowGuarantorResourceKeys(): array
{
    return [
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
        'dispute_resolution',
        'requester',
        'counterparty',
        'installments',
        'company_detail',
        'status_histories',
        'media',
        'overdue_at',
        'ended_at',
        'cancelled_at',
        'rejected_at',
        'refunded_at',
        'created_at',
    ];
}

test('store individual GuarantorResource key set is frozen', function () {
    $requester = User::factory()->create();
    $counterparty = User::factory()->create([
        'phone' => (string) Phone::make('0501112233'),
    ]);
    Sanctum::actingAs($requester);

    $data = $this->post(route('api.v1.guarantor.guarantor.store.individual'), [
        'counterparty_phone' => '0501112233',
        'amount' => 1000,
        'title' => 'Freeze individual',
        'description' => 'Shape lock',
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
    ], [
        'Accept' => 'application/json',
    ])
        ->assertSuccessful()
        ->json('data');

    expect(array_keys($data))->toBe(frozenIndividualGuarantorResourceKeys())
        ->and($data['type'])->toHaveKeys(['value', 'label', 'color'])
        ->and($data['status'])->toHaveKeys(['value', 'label', 'color'])
        ->and($data['media'])->toBeArray()
        ->and($counterparty->id)->not->toBeNull();
});

test('store company GuarantorResource key set is frozen', function () {
    $requester = User::factory()->create();
    User::factory()->create(['phone' => (string) Phone::make('0502223344')]);
    Sanctum::actingAs($requester);

    // Company API FormRequest uses total_amount (not amount/title); build via service
    // path that mirrors storeCompany loads, then freeze GuarantorResource keys.
    $guarantorRequest = GuarantorRequest::factory()->company()->pendingAdmin()->create([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'title' => 'Freeze company',
        'description' => 'Shape lock',
        'amount' => 1000,
        'project_type' => 'Construction',
    ]);

    $guarantorRequest->companyDetail()->create([
        'company_name' => 'Acme Corp',
        'commercial_register' => 'CR-1',
        'authorized_name' => 'Auth Name',
        'authorized_id_number' => '123',
        'authorization_type' => AuthorizationTypeEnum::PowerOfAttorney,
        'requester_account_holder' => 'Holder',
        'requester_iban' => 'SA123',
        'counterparty_account_holder' => 'CP Holder',
    ]);

    $guarantorRequest->load([
        'requester',
        'counterparty',
        'installments',
        'companyDetail.media',
        'media',
    ]);

    // Match CreateCompanyGuarantorAction response loads only (no status_histories / counts).
    $data = collect(GuarantorResource::make($guarantorRequest)->toArray(request()))
        ->except(['status_histories', 'installments_count'])
        ->all();

    if (isset($data['company_detail']) && is_object($data['company_detail'])) {
        $data['company_detail'] = $data['company_detail']->toArray(request());
    }

    expect(array_keys($data))->toBe(frozenCompanyGuarantorResourceKeys())
        ->and($data['company_detail'])->toBeArray()
        ->and($data['company_detail'])->toHaveKeys([
            'id',
            'company_name',
            'commercial_register',
            'authorized_name',
            'authorized_id_number',
            'authorization_type',
            'requester_account_holder',
            'requester_iban',
            'requester_bank',
            'counterparty_account_holder',
            'counterparty_iban',
            'counterparty_bank',
            'terms_notes',
            'media',
        ]);
});

test('update GuarantorResource key set is frozen', function () {
    $requester = User::factory()->create();
    $guarantorRequest = GuarantorRequest::factory()->pendingAdmin()->create([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'title' => 'Before update',
    ]);
    Sanctum::actingAs($requester);

    $data = $this->post(route('api.v1.guarantor.guarantor.update', $guarantorRequest), [
        'title' => 'After update',
    ], [
        'Accept' => 'application/json',
    ])
        ->assertSuccessful()
        ->json('data');

    expect(array_keys($data))->toBe(frozenShowGuarantorResourceKeys())
        ->and($data['title'])->toBe('After update');
});

test('destroy response envelope is frozen', function () {
    $requester = User::factory()->create();
    $guarantorRequest = GuarantorRequest::factory()->pendingAdmin()->create([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
    ]);
    Sanctum::actingAs($requester);

    $response = $this->deleteJson(route('api.v1.guarantor.guarantor.destroy', $guarantorRequest))
        ->assertSuccessful()
        ->json();

    expect($response)->toHaveKeys(['success', 'message'])
        ->and($response['success'])->toBeTrue()
        ->and($response['message'])->toBeString()->not->toBeEmpty()
        ->and(GuarantorRequest::withTrashed()->find($guarantorRequest->id)?->trashed())->toBeTrue();
});

test('deleteMedia response envelope is frozen', function () {
    $requester = User::factory()->create();
    $guarantorRequest = GuarantorRequest::factory()->pendingAdmin()->create([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'status' => GuarantorStatusEnum::PendingAdmin,
    ]);

    $media = $guarantorRequest
        ->addMedia(UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))
        ->toMediaCollection('files');

    Sanctum::actingAs($requester);

    $response = $this->deleteJson(route('api.v1.guarantor.guarantor.deleteMedia', [
        'guarantorRequest' => $guarantorRequest,
        'media' => $media->uuid,
    ]))
        ->assertSuccessful()
        ->json();

    expect($response)->toHaveKeys(['success', 'message'])
        ->and($response['success'])->toBeTrue()
        ->and($response['message'])->toBeString()->not->toBeEmpty();
});
