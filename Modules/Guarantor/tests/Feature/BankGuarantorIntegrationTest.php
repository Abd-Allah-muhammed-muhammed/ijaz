<?php

use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Catalog\Models\Bank;
use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;
use Modules\Guarantor\Http\Resources\Api\CompanyDetailResource;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Modules\Guarantor\Models\GuarantorRequest;

const BANK_TEST_COUNTERPARTY_PHONE = '0507766554';

beforeEach(function () {
    Notification::fake();
});

function bankTestActors(): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create([
        'phone' => (string) Phone::make(BANK_TEST_COUNTERPARTY_PHONE),
    ]);

    return compact('requester', 'counterparty');
}

function activeBank(?array $translations = null): Bank
{
    return Bank::factory()->create([
        'translations' => $translations ?? geoNameTranslations('Test Bank'),
    ]);
}

function inactiveBank(): Bank
{
    return Bank::factory()->inactive()->create([
        'translations' => geoNameTranslations('Inactive Bank'),
    ]);
}

function companyBankPayload(Bank $requesterBank, ?Bank $counterpartyBank = null, ?string $termsNotes = null): array
{
    return [
        'counterparty_phone' => BANK_TEST_COUNTERPARTY_PHONE,
        'project_type' => 'Construction',
        'total_amount' => 1000,
        'installments' => [
            ['order' => 1, 'amount' => 1000, 'due_date' => now()->addDays(5)->toDateString()],
        ],
        'company_name' => 'Acme Corp',
        'commercial_register' => 'CR-123456',
        'authorized_name' => 'John Doe',
        'authorized_id_number' => '1234567890',
        'authorization_type' => 'owner',
        'requester_account_holder' => 'Requester Name',
        'requester_iban' => 'SA1234567890123456789012',
        'requester_bank_id' => $requesterBank->id,
        'counterparty_account_holder' => 'Counterparty Name',
        'counterparty_bank_id' => $counterpartyBank?->id,
        'terms_notes' => $termsNotes,
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
        'authorized_id' => UploadedFile::fake()->create('authorized_id.pdf', 100, 'application/pdf'),
        'contracts' => [
            UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
        ],
        'iban_certificate' => UploadedFile::fake()->create('iban.pdf', 100, 'application/pdf'),
        'cr_file' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf'),
        'articles_of_association' => UploadedFile::fake()->create('aoa.pdf', 100, 'application/pdf'),
        'national_address_file' => UploadedFile::fake()->create('national-address.pdf', 100, 'application/pdf'),
    ];
}

test('StoreCompanyGuarantorRequest requires requester_bank_id and validates it exists among active banks', function () {
    ['requester' => $requester] = bankTestActors();
    Sanctum::actingAs($requester);
    $active = activeBank();
    inactiveBank();

    $validator = validator(
        array_merge(companyBankPayload($active), ['requester_bank_id' => null]),
        (new StoreCompanyGuarantorRequest)->rules(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('requester_bank_id'))->toBeTrue();

    $inactive = inactiveBank();

    $validatorInactive = validator(
        array_merge(companyBankPayload($active), ['requester_bank_id' => $inactive->id]),
        (new StoreCompanyGuarantorRequest)->rules(),
    );

    expect($validatorInactive->fails())->toBeTrue()
        ->and($validatorInactive->errors()->has('requester_bank_id'))->toBeTrue();
});

test('StoreCompanyGuarantorRequest allows counterparty_bank_id to be nullable, validated against active banks when present', function () {
    ['requester' => $requester] = bankTestActors();
    Sanctum::actingAs($requester);
    $active = activeBank();
    $inactive = inactiveBank();

    $nullableValidator = validator(
        companyBankPayload($active),
        (new StoreCompanyGuarantorRequest)->rules(),
    );

    expect($nullableValidator->fails())->toBeFalse();

    $inactiveValidator = validator(
        array_merge(companyBankPayload($active), ['counterparty_bank_id' => $inactive->id]),
        (new StoreCompanyGuarantorRequest)->rules(),
    );

    expect($inactiveValidator->fails())->toBeTrue()
        ->and($inactiveValidator->errors()->has('counterparty_bank_id'))->toBeTrue();
});

test('terms_notes is nullable, optional, max length enforced, and persists correctly on GuarantorCompanyDetail', function () {
    ['requester' => $requester] = bankTestActors();
    Sanctum::actingAs($requester);

    $bank = activeBank();
    $terms = str_repeat('a', 2000);

    $response = $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyBankPayload($bank, null, $terms),
        ['Accept' => 'application/json'],
    );

    $response->assertSuccessful();

    $detail = GuarantorCompanyDetail::query()->latest('created_at')->first();
    expect($detail?->terms_notes)->toBe($terms);

    $tooLong = validator(
        array_merge(companyBankPayload($bank), ['terms_notes' => str_repeat('a', 2001)]),
        (new StoreCompanyGuarantorRequest)->rules(),
    );

    expect($tooLong->fails())->toBeTrue()
        ->and($tooLong->errors()->has('terms_notes'))->toBeTrue();
});

test('CompanyDetailResource requester_bank/counterparty_bank objects use BankResource shape for both parties, plus terms_notes as a plain string', function () {
    $requesterBank = activeBank(geoNameTranslations('Requester Bank'));
    $requesterBank->addMedia(UploadedFile::fake()->image('rb.png', 32, 32))->toMediaCollection('logo');

    $counterpartyBank = activeBank(geoNameTranslations('Counterparty Bank'));

    $guarantorRequest = GuarantorRequest::factory()->company()->pendingAdmin()->create();
    $detail = $guarantorRequest->companyDetail()->create([
        'company_name' => 'Acme Corp',
        'commercial_register' => 'CR-1',
        'authorized_name' => 'Auth Name',
        'authorized_id_number' => '123',
        'authorization_type' => 'owner',
        'requester_account_holder' => 'Holder',
        'requester_iban' => 'SA1234567890123456789012',
        'requester_bank_id' => $requesterBank->id,
        'counterparty_account_holder' => 'CP Holder',
        'counterparty_bank_id' => $counterpartyBank->id,
        'terms_notes' => 'Custom terms apply.',
    ]);

    $detail->load(['requesterBank', 'counterpartyBank']);

    $data = CompanyDetailResource::make($detail)->resolve(request());

    expect($data)->toHaveKeys(['requester_bank', 'counterparty_bank', 'terms_notes'])
        ->and($data['requester_bank'])->toMatchArray([
            'id' => $requesterBank->id,
            'name' => 'Requester Bank EN',
            'is_active' => true,
        ])
        ->and($data['requester_bank']['logo'])->toBe($requesterBank->getLogoUrl())
        ->and($data['requester_bank'])->not->toHaveKeys(['value', 'label', 'logo_url'])
        ->and($data['counterparty_bank'])->toMatchArray([
            'id' => $counterpartyBank->id,
            'name' => 'Counterparty Bank EN',
            'logo' => null,
            'is_active' => true,
        ])
        ->and($data['terms_notes'])->toBe('Custom terms apply.');
});
