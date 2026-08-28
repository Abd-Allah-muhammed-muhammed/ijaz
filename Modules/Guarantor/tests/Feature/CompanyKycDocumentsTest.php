<?php

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Actions\Guarantor\AcceptGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CreateCompanyGuarantorAction;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorAcceptUploadData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\GuarantorUploadData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Http\Requests\StoreIndividualGuarantorRequest;
use Modules\Guarantor\Http\Resources\Api\CompanyDetailResource;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Modules\Guarantor\Models\GuarantorRequest;

test('Company creation requires all 4 requester documents: iban_certificate, cr_file, articles_of_association, national_address_file', function () {
    setupGuarantorActors();

    $validator = validateCompanyGuarantorRequest(
        companyGuarantorPayload(),
        companyGuarantorFiles(),
    );

    expect($validator->fails())->toBeFalse();
});

test('Company creation fails with a clear validation error if any requester document is missing', function () {
    setupGuarantorActors();

    foreach (['iban_certificate', 'cr_file', 'articles_of_association', 'national_address_file'] as $field) {
        $files = companyGuarantorFiles();
        unset($files[$field]);

        $validator = validateCompanyGuarantorRequest(
            companyGuarantorPayload(),
            $files,
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has($field))->toBeTrue();
    }
});

test('the renamed requester_iban_certificate collection receives the upload correctly (renamed from iban_certificates)', function () {
    ['requester' => $requester] = setupGuarantorActors();

    $guarantorRequest = app(CreateCompanyGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'KYC test',
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
            authorization_type: 'owner',
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
            Request::create('/', 'POST', [], [], companyGuarantorFiles()),
        ),
    );

    $detail = $guarantorRequest->companyDetail;

    expect($detail->getMedia('requester_iban_certificate'))->toHaveCount(1)
        ->and($detail->getMedia('iban_certificates'))->toHaveCount(0)
        ->and($detail->getFirstMedia('requester_iban_certificate')?->file_name)->toBe('iban.pdf');
});

test('existing authorized_id, contracts, and company_documents fields are completely unaffected — regression, still required/optional exactly as before', function () {
    setupGuarantorActors();

    $filesWithoutContracts = companyGuarantorFiles();
    unset($filesWithoutContracts['contracts']);

    $validatorMissingContracts = validateCompanyGuarantorRequest(
        companyGuarantorPayload(),
        $filesWithoutContracts,
    );

    expect($validatorMissingContracts->fails())->toBeTrue()
        ->and($validatorMissingContracts->errors()->has('contracts'))->toBeTrue();

    $filesWithoutAuthorizedId = companyGuarantorFiles();
    unset($filesWithoutAuthorizedId['authorized_id']);

    $validatorMissingAuthorizedId = validateCompanyGuarantorRequest(
        companyGuarantorPayload(),
        $filesWithoutAuthorizedId,
    );

    expect($validatorMissingAuthorizedId->fails())->toBeTrue()
        ->and($validatorMissingAuthorizedId->errors()->has('authorized_id'))->toBeTrue();

    $validatorWithoutCompanyDocuments = validateCompanyGuarantorRequest(
        companyGuarantorPayload(),
        companyGuarantorFiles(),
    );

    expect($validatorWithoutCompanyDocuments->fails())->toBeFalse();
});

test('Individual guarantor creation is unaffected — none of these new fields apply', function () {
    ['counterparty' => $counterparty] = setupGuarantorActors('0509998877');

    $this->post(route('api.v1.guarantor.guarantor.store.individual'), [
        'counterparty_phone' => '0509998877',
        'title' => 'Individual only',
        'description' => 'Desc',
        'amount' => 1000,
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
    ])->assertSuccessful();

    $validator = Validator::make(
        [
            'counterparty_phone' => (string) $counterparty->phone,
            'title' => 'Individual only',
            'description' => 'Desc',
            'amount' => 1000,
        ],
        (new StoreIndividualGuarantorRequest)->rules(),
    );

    expect($validator->errors()->has('iban_certificate'))->toBeFalse()
        ->and($validator->errors()->has('cr_file'))->toBeFalse();
});

test('POST /guarantor/{id}/accept for a Company guarantor now requires all 4 counterparty documents in addition to the existing signature requirement', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->approvedByAdmin()->create();
    attachGuarantorCompanyDetail($guarantorRequest);
    $counterparty = $guarantorRequest->counterparty;
    Sanctum::actingAs($counterparty);

    $this->post(route('api.v1.guarantor.guarantor.accept', $guarantorRequest), companyGuarantorAcceptFiles())
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', GuarantorStatusEnum::Accepted->value);

    $detail = $guarantorRequest->fresh()->companyDetail;

    expect($detail->getMedia('counterparty_iban_certificate'))->toHaveCount(1)
        ->and($detail->getMedia('counterparty_cr_file'))->toHaveCount(1)
        ->and($detail->getMedia('counterparty_articles_of_association'))->toHaveCount(1)
        ->and($detail->getMedia('counterparty_national_address_file'))->toHaveCount(1)
        ->and($guarantorRequest->fresh()->getMedia('counterparty_signature'))->toHaveCount(1);
});

test('POST /guarantor/{id}/accept for an Individual guarantor is unaffected — only signature required, company document fields are not validated', function () {
    $guarantorRequest = GuarantorRequest::factory()->approvedByAdmin()->create([
        'type' => GuarantorTypeEnum::Individual,
    ]);
    $counterparty = $guarantorRequest->counterparty;
    Sanctum::actingAs($counterparty);

    $this->post(route('api.v1.guarantor.guarantor.accept', $guarantorRequest), [
        'signature' => UploadedFile::fake()->create('cp-signature.pdf', 100, 'application/pdf'),
    ])->assertSuccessful();
});

test('accept fails with a clear validation error if any counterparty document is missing (Company only)', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->approvedByAdmin()->create();
    attachGuarantorCompanyDetail($guarantorRequest);
    $counterparty = $guarantorRequest->counterparty;
    Sanctum::actingAs($counterparty);

    foreach (['iban_certificate', 'cr_file', 'articles_of_association', 'national_address_file'] as $field) {
        $files = companyGuarantorAcceptFiles();
        unset($files[$field]);

        $this->post(route('api.v1.guarantor.guarantor.accept', $guarantorRequest), $files)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);
    }
});

test('all 8 document collections (4 requester + 4 counterparty) are correctly attached and retrievable via getMedia() after a full create+accept flow', function () {
    ['requester' => $requester, 'counterparty' => $counterparty] = setupGuarantorActors();

    $guarantorRequest = app(CreateCompanyGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Full KYC flow',
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
            authorization_type: 'owner',
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
            Request::create('/', 'POST', [], [], companyGuarantorFiles()),
        ),
    );

    $guarantorRequest->update(['status' => GuarantorStatusEnum::ApprovedByAdmin]);

    app(AcceptGuarantorAction::class)->handle(
        $guarantorRequest,
        $counterparty,
        new GuarantorAcceptUploadData(
            signature: companyGuarantorAcceptFiles()['signature'],
            ibanCertificate: companyGuarantorAcceptFiles()['iban_certificate'],
            crFile: companyGuarantorAcceptFiles()['cr_file'],
            articlesOfAssociation: companyGuarantorAcceptFiles()['articles_of_association'],
            nationalAddressFile: companyGuarantorAcceptFiles()['national_address_file'],
        ),
    );

    $detail = $guarantorRequest->fresh()->companyDetail;

    foreach ([
        'requester_iban_certificate',
        'requester_cr_file',
        'requester_articles_of_association',
        'requester_national_address_file',
        'counterparty_iban_certificate',
        'counterparty_cr_file',
        'counterparty_articles_of_association',
        'counterparty_national_address_file',
    ] as $collection) {
        expect($detail->getMedia($collection))->toHaveCount(1, "Expected media in {$collection}");
    }
});

test('CompanyDetailResource exposes all 8 documents grouped clearly by party', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->create();
    $detail = GuarantorCompanyDetail::query()->create([
        'guarantor_request_id' => $guarantorRequest->id,
        'company_name' => 'Acme',
        'commercial_register' => '123',
        'authorized_name' => 'Auth',
        'authorized_id_number' => '1',
        'authorization_type' => AuthorizationTypeEnum::Owner,
        'requester_account_holder' => 'Holder',
        'requester_iban' => 'SA0380000000608010167519',
        'requester_bank_id' => defaultGuarantorTestBankId(),
        'counterparty_account_holder' => 'CP Holder',
    ]);

    foreach ([
        'requester_iban_certificate' => 'req-iban.pdf',
        'requester_cr_file' => 'req-cr.pdf',
        'requester_articles_of_association' => 'req-aoa.pdf',
        'requester_national_address_file' => 'req-na.pdf',
        'counterparty_iban_certificate' => 'cp-iban.pdf',
        'counterparty_cr_file' => 'cp-cr.pdf',
        'counterparty_articles_of_association' => 'cp-aoa.pdf',
        'counterparty_national_address_file' => 'cp-na.pdf',
    ] as $collection => $fileName) {
        $detail->addMedia(UploadedFile::fake()->create($fileName, 100, 'application/pdf'))
            ->toMediaCollection($collection);
    }

    $detail->load('media');
    $data = CompanyDetailResource::make($detail)->toArray(request());

    expect($data)->toHaveKeys(['requester_documents', 'counterparty_documents'])
        ->and($data['requester_documents'])->toHaveKeys([
            'iban_certificate',
            'cr_file',
            'articles_of_association',
            'national_address_file',
        ])
        ->and($data['counterparty_documents'])->toHaveKeys([
            'iban_certificate',
            'cr_file',
            'articles_of_association',
            'national_address_file',
        ])
        ->and($data['requester_documents']['iban_certificate']['file_name'])->toBe('req-iban.pdf')
        ->and($data['counterparty_documents']['national_address_file']['file_name'])->toBe('cp-na.pdf');
});
