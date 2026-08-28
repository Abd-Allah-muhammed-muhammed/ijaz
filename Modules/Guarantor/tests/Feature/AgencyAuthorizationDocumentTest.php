<?php

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Actions\Guarantor\CreateCompanyGuarantorAction;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\GuarantorUploadData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Http\Resources\Api\CompanyDetailResource;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Modules\Guarantor\Models\GuarantorRequest;

beforeEach(function () {
    Notification::fake();
});

test('creating a Company guarantor with authorization_type=agency requires agency_authorization_document (renamed field) — old power_of_attorney_document key no longer works', function () {
    setupGuarantorActors();

    $validatorMissing = validateCompanyGuarantorRequest(
        companyGuarantorPayload(['authorization_type' => 'agency']),
        companyGuarantorFiles(),
    );

    expect($validatorMissing->fails())->toBeTrue()
        ->and($validatorMissing->errors()->has('agency_authorization_document'))->toBeTrue()
        ->and($validatorMissing->errors()->has('power_of_attorney_document'))->toBeFalse();

    $filesWithOldKey = companyGuarantorFiles();
    $filesWithOldKey['power_of_attorney_document'] = UploadedFile::fake()->create('poa.pdf', 100, 'application/pdf');

    $validatorOldKey = validateCompanyGuarantorRequest(
        companyGuarantorPayload(['authorization_type' => 'agency']),
        $filesWithOldKey,
    );

    expect($validatorOldKey->fails())->toBeTrue()
        ->and($validatorOldKey->errors()->has('agency_authorization_document'))->toBeTrue();
});

test('the media collection is named agency_authorization_document, not power_of_attorney_document', function () {
    ['requester' => $requester] = setupGuarantorActors();

    $files = companyGuarantorFiles();
    $files['agency_authorization_document'] = UploadedFile::fake()->create('agency-auth.pdf', 100, 'application/pdf');

    $guarantorRequest = app(CreateCompanyGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Agency auth store test',
            description: '',
            amount: 1000,
            counterparty_phone: '0501234567',
            project_type: 'Construction',
        ),
        new CompanyDetailData(
            company_name: 'Acme',
            commercial_register: 'CR-1',
            region_id: null,
            city_id: null,
            authorized_name: 'Auth',
            authorized_id_number: '1',
            authorization_type: 'agency',
            requester_account_holder: 'Holder',
            requester_iban: 'SA0380000000608010167519',
            requester_bank_id: defaultGuarantorTestBankId(),
            counterparty_account_holder: 'CP',
        ),
        [
            new InstallmentData(1, 500, now()->addDays(3)->toDateString()),
            new InstallmentData(2, 500, now()->addDays(30)->toDateString()),
        ],
        $requester,
        GuarantorUploadData::fromCompanyRequest(
            Request::create('/', 'POST', [], [], $files),
        ),
    );

    $detail = $guarantorRequest->companyDetail;

    expect($detail->getMedia('agency_authorization_document'))->toHaveCount(1)
        ->and($detail->getFirstMedia('agency_authorization_document')?->file_name)->toBe('agency-auth.pdf')
        ->and($detail->getMedia('power_of_attorney_document'))->toHaveCount(0);
});

test('CompanyDetailResource exposes agency_authorization_document, not power_of_attorney_document', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->create();
    $detail = GuarantorCompanyDetail::query()->create([
        'guarantor_request_id' => $guarantorRequest->id,
        'company_name' => 'Acme',
        'commercial_register' => '123',
        'authorized_name' => 'Auth',
        'authorized_id_number' => '1',
        'authorization_type' => AuthorizationTypeEnum::Agency,
        'requester_account_holder' => 'Holder',
        'requester_iban' => 'SA0380000000608010167519',
        'requester_bank_id' => defaultGuarantorTestBankId(),
        'counterparty_account_holder' => 'CP Holder',
    ]);

    $detail->load('media');
    $empty = CompanyDetailResource::make($detail)->response(request())->getData(true);

    expect($empty['requester_documents'])->toHaveKey('agency_authorization_document')
        ->and($empty['requester_documents'])->not->toHaveKey('power_of_attorney_document')
        ->and($empty['requester_documents']['agency_authorization_document'])->toBeNull();

    $detail->addMedia(UploadedFile::fake()->create('agency-auth.pdf', 100, 'application/pdf'))
        ->toMediaCollection('agency_authorization_document');
    $detail->load('media');

    $present = CompanyDetailResource::make($detail)->response(request())->getData(true);

    expect($present['requester_documents']['agency_authorization_document'])->toHaveKeys([
        'id', 'name', 'collection_name', 'file_name', 'mime_type', 'type', 'url', 'extension', 'size',
    ])
        ->and($present['requester_documents']['agency_authorization_document']['file_name'])->toBe('agency-auth.pdf')
        ->and($present['requester_documents']['agency_authorization_document']['collection_name'])->toBe('agency_authorization_document')
        ->and($present['requester_documents'])->not->toHaveKey('power_of_attorney_document');
});

test('owner/manager creation is unaffected — regression', function () {
    setupGuarantorActors('0501112233');

    $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        array_merge(
            companyGuarantorPayload([
                'counterparty_phone' => '0501112233',
                'authorization_type' => 'owner',
            ]),
            companyGuarantorFiles(),
        ),
        ['Accept' => 'application/json'],
    )->assertSuccessful();

    setupGuarantorActors('0501112244');

    $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        array_merge(
            companyGuarantorPayload([
                'counterparty_phone' => '0501112244',
                'authorization_type' => 'manager',
            ]),
            companyGuarantorFiles(),
        ),
        ['Accept' => 'application/json'],
    )->assertSuccessful();

    $ownerDetail = GuarantorCompanyDetail::query()->where('authorization_type', 'owner')->latest('id')->first();
    $managerDetail = GuarantorCompanyDetail::query()->where('authorization_type', 'manager')->latest('id')->first();

    expect($ownerDetail)->not->toBeNull()
        ->and($ownerDetail->getMedia('agency_authorization_document'))->toHaveCount(0)
        ->and($managerDetail)->not->toBeNull()
        ->and($managerDetail->getMedia('agency_authorization_document'))->toHaveCount(0);
});

test('creating a Company guarantor with authorization_type=agency requires agency_authorization_document — missing file is rejected with a clear validation error', function () {
    setupGuarantorActors();

    $validator = validateCompanyGuarantorRequest(
        companyGuarantorPayload(['authorization_type' => 'agency']),
        companyGuarantorFiles(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('agency_authorization_document'))->toBeTrue();
});

test('existing authorization_type validation/persistence is otherwise unchanged — regression against AuthorizationTypeEnumTest', function () {
    expect(AuthorizationTypeEnum::cases())->toHaveCount(3)
        ->and(AuthorizationTypeEnum::Owner->value)->toBe('owner')
        ->and(AuthorizationTypeEnum::Manager->value)->toBe('manager')
        ->and(AuthorizationTypeEnum::Agency->value)->toBe('agency');

    $requester = setupGuarantorActors('0509988111')['requester'];
    Sanctum::actingAs($requester);

    foreach (['owner', 'manager'] as $authorizationType) {
        $payload = array_merge(
            companyGuarantorPayload([
                'counterparty_phone' => '0509988111',
                'authorization_type' => $authorizationType,
            ]),
            companyGuarantorFiles(),
        );

        $this->post(
            route('api.v1.guarantor.guarantor.store.company'),
            $payload,
            ['Accept' => 'application/json'],
        )->assertSuccessful();
    }

    $agencyFiles = companyGuarantorFiles();
    $agencyFiles['agency_authorization_document'] = UploadedFile::fake()->create('agency-auth.pdf', 100, 'application/pdf');

    $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        array_merge(
            companyGuarantorPayload([
                'counterparty_phone' => '0509988111',
                'authorization_type' => 'agency',
            ]),
            $agencyFiles,
        ),
        ['Accept' => 'application/json'],
    )->assertSuccessful();

    expect(
        GuarantorCompanyDetail::query()->where('authorization_type', 'owner')->exists()
    )->toBeTrue()
        ->and(
            GuarantorCompanyDetail::query()->where('authorization_type', 'manager')->exists()
        )->toBeTrue()
        ->and(
            GuarantorCompanyDetail::query()->where('authorization_type', 'agency')->exists()
        )->toBeTrue();
});
