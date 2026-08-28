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

test('creating a Company guarantor with authorization_type=agency requires a power_of_attorney_document file — missing file is rejected with a clear validation error', function () {
    setupGuarantorActors();

    $validator = validateCompanyGuarantorRequest(
        companyGuarantorPayload(['authorization_type' => 'agency']),
        companyGuarantorFiles(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('power_of_attorney_document'))->toBeTrue();
});

test('creating a Company guarantor with authorization_type=owner does not require the document — creation succeeds without it', function () {
    ['requester' => $requester] = setupGuarantorActors();

    $payload = array_merge(
        companyGuarantorPayload(['authorization_type' => 'owner']),
        companyGuarantorFiles(),
    );

    $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        $payload,
        ['Accept' => 'application/json'],
    )->assertSuccessful();

    $detail = GuarantorCompanyDetail::query()->latest('id')->first();

    expect($detail)->not->toBeNull()
        ->and($detail->authorization_type)->toBe(AuthorizationTypeEnum::Owner)
        ->and($detail->getMedia('power_of_attorney_document'))->toHaveCount(0);
});

test('creating a Company guarantor with authorization_type=manager does not require the document — creation succeeds without it', function () {
    ['requester' => $requester] = setupGuarantorActors();

    $payload = array_merge(
        companyGuarantorPayload(['authorization_type' => 'manager']),
        companyGuarantorFiles(),
    );

    $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        $payload,
        ['Accept' => 'application/json'],
    )->assertSuccessful();

    $detail = GuarantorCompanyDetail::query()->latest('id')->first();

    expect($detail)->not->toBeNull()
        ->and($detail->authorization_type)->toBe(AuthorizationTypeEnum::Manager)
        ->and($detail->getMedia('power_of_attorney_document'))->toHaveCount(0);
});

test('when provided, the power_of_attorney_document file is correctly stored and retrievable', function () {
    ['requester' => $requester] = setupGuarantorActors();

    $files = companyGuarantorFiles();
    $files['power_of_attorney_document'] = UploadedFile::fake()->create('poa.pdf', 100, 'application/pdf');

    $guarantorRequest = app(CreateCompanyGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'PoA store test',
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

    expect($detail->getMedia('power_of_attorney_document'))->toHaveCount(1)
        ->and($detail->getFirstMedia('power_of_attorney_document')?->file_name)->toBe('poa.pdf');
});

test('CompanyDetailResource exposes power_of_attorney_document as a MediaResource-shaped object when present, null otherwise', function () {
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

    expect($empty['requester_documents']['power_of_attorney_document'])->toBeNull();

    $detail->addMedia(UploadedFile::fake()->create('poa.pdf', 100, 'application/pdf'))
        ->toMediaCollection('power_of_attorney_document');
    $detail->load('media');

    $present = CompanyDetailResource::make($detail)->response(request())->getData(true);

    expect($present['requester_documents']['power_of_attorney_document'])->toHaveKeys([
        'id', 'name', 'collection_name', 'file_name', 'mime_type', 'type', 'url', 'extension', 'size',
    ])
        ->and($present['requester_documents']['power_of_attorney_document']['file_name'])->toBe('poa.pdf')
        ->and($present['requester_documents']['power_of_attorney_document']['collection_name'])->toBe('power_of_attorney_document');
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
    $agencyFiles['power_of_attorney_document'] = UploadedFile::fake()->create('poa.pdf', 100, 'application/pdf');

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
