<?php

use App\Http\Resources\Api\V1\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Catalog\Http\Resources\Api\V1\BankResource;
use Modules\Catalog\Models\Bank;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Http\Resources\Api\CompanyDetailResource;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Modules\Guarantor\Models\GuarantorRequest;

/**
 * @return array<string, mixed>
 */
function companyDetailResourceArray(GuarantorCompanyDetail $detail): array
{
    $request = Request::create('/');

    return CompanyDetailResource::make($detail)->resolve($request);
}

/**
 * @return list<string>
 */
function publicBankApiShapeKeys(): array
{
    return ['id', 'name', 'logo', 'is_active'];
}

/**
 * @return list<string>
 */
function mediaResourceShapeKeys(): array
{
    return array_keys(MediaResource::make(
        GuarantorRequest::factory()->create()
            ->addMedia(UploadedFile::fake()->create('probe.pdf', 100, 'application/pdf'))
            ->toMediaCollection('files')
    )->resolve());
}

test('requester_bank and counterparty_bank on CompanyDetailResource now use the exact same shape as the public Bank catalog API (id, name, logo, is_active)', function () {
    $requesterBank = Bank::factory()->create(['translations' => geoNameTranslations('Shape Requester Bank')]);
    $requesterBank->addMedia(UploadedFile::fake()->image('rb.png', 32, 32))->toMediaCollection('logo');

    $counterpartyBank = Bank::factory()->create(['translations' => geoNameTranslations('Shape Counterparty Bank')]);

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
        'requester_bank_id' => $requesterBank->id,
        'counterparty_account_holder' => 'CP Holder',
        'counterparty_bank_id' => $counterpartyBank->id,
    ]);

    $detail->load(['requesterBank', 'counterpartyBank']);

    $catalogItem = $this->getJson('/api/v1/catalog/banks?search=Shape+Requester')
        ->assertSuccessful()
        ->json('data.items.0');

    expect(array_keys($catalogItem))->toBe(publicBankApiShapeKeys());

    $data = companyDetailResourceArray($detail);

    expect(array_keys($data['requester_bank']))->toBe(publicBankApiShapeKeys())
        ->and(array_keys($data['counterparty_bank']))->toBe(publicBankApiShapeKeys())
        ->and($data['requester_bank']['id'])->toBe($requesterBank->id)
        ->and($data['requester_bank']['name'])->toBe('Shape Requester Bank EN')
        ->and($data['requester_bank']['logo'])->toBe($requesterBank->getLogoUrl())
        ->and($data['requester_bank']['is_active'])->toBeTrue()
        ->and($data['requester_bank'])->not->toHaveKeys(['value', 'label'])
        ->and($data['counterparty_bank']['name'])->toBe('Shape Counterparty Bank EN');
});

test('the 8 KYC document fields (requester_documents, counterparty_documents) now use the exact same shape as the existing top-level media field (via MediaResource)', function () {
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

    $detail->addMedia(UploadedFile::fake()->create('req-iban.pdf', 100, 'application/pdf'))
        ->toMediaCollection('requester_iban_certificate');
    $detail->addMedia(UploadedFile::fake()->create('cp-cr.pdf', 100, 'application/pdf'))
        ->toMediaCollection('counterparty_cr_file');

    $detail->load('media');

    $data = companyDetailResourceArray($detail);
    $expectedKeys = mediaResourceShapeKeys();

    expect(array_keys($data['media'][0]))->toBe($expectedKeys)
        ->and(array_keys($data['requester_documents']['iban_certificate']))->toBe($expectedKeys)
        ->and(array_keys($data['counterparty_documents']['cr_file']))->toBe($expectedKeys)
        ->and($data['requester_documents']['iban_certificate']['file_name'])->toBe('req-iban.pdf')
        ->and($data['counterparty_documents']['cr_file']['collection_name'])->toBe('counterparty_cr_file');
});

test('null is returned correctly when a bank or document is not yet set, matching prior behavior', function () {
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
        'counterparty_bank_id' => null,
    ]);

    $detail->load(['requesterBank', 'counterpartyBank', 'media']);

    $data = companyDetailResourceArray($detail);

    expect($data['counterparty_bank'])->toBeNull()
        ->and($data['requester_documents']['cr_file'])->toBeNull()
        ->and($data['counterparty_documents']['iban_certificate'])->toBeNull();

    $withoutMedia = GuarantorCompanyDetail::query()->findOrFail($detail->id);
    $withoutMedia->load(['requesterBank', 'counterpartyBank']);
    $withoutMedia->unsetRelation('media');

    $dataWithoutMedia = companyDetailResourceArray($withoutMedia);

    expect($dataWithoutMedia['requester_documents']['iban_certificate'])->toBeNull()
        ->and($dataWithoutMedia)->not->toHaveKey('media');
});

test('CompanyDetailResource bank objects match BankResource output directly', function () {
    $bank = Bank::factory()->create(['translations' => geoNameTranslations('Direct Bank Resource')]);

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
        'requester_bank_id' => $bank->id,
        'counterparty_account_holder' => 'CP Holder',
    ]);

    $detail->load('requesterBank');

    $data = companyDetailResourceArray($detail);
    $expected = BankResource::make($bank)->resolve();

    expect($data['requester_bank'])->toBe($expected);
});
