<?php

use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\GuarantorUploadData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;
use Modules\Guarantor\Services\GuarantorService;

const COMPANY_AMOUNT_COUNTERPARTY_PHONE = '0509988776';

beforeEach(function () {
    Notification::fake();
});

/**
 * @return array{requester: User, counterparty: User}
 */
function companyAmountMappingActors(): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create([
        'phone' => (string) Phone::make(COMPANY_AMOUNT_COUNTERPARTY_PHONE),
    ]);

    return compact('requester', 'counterparty');
}

/**
 * @return array{0: StoreCompanyGuarantorRequest, 1: array<string, mixed>}
 */
function validatedCompanyGuarantorFormRequest(float $totalAmount = 50000.0): array
{
    $half = $totalAmount / 2;

    $payload = [
        'counterparty_phone' => COMPANY_AMOUNT_COUNTERPARTY_PHONE,
        'project_type' => 'Construction',
        'total_amount' => $totalAmount,
        'installments' => [
            ['order' => 1, 'amount' => $half, 'due_date' => now()->addDays(3)->toDateString()],
            ['order' => 2, 'amount' => $half, 'due_date' => now()->addDays(30)->toDateString()],
        ],
        'company_name' => 'Acme Corp',
        'commercial_register' => 'CR-123456',
        'authorized_name' => 'John Doe',
        'authorized_id_number' => '1234567890',
        'authorization_type' => 'owner',
        'requester_account_holder' => 'Requester Name',
        'requester_iban' => 'SA1234567890123456789012',
        'requester_bank_id' => defaultGuarantorTestBankId(),
        'counterparty_account_holder' => 'Counterparty Name',
    ];

    $files = companyGuarantorFiles();

    $formRequest = StoreCompanyGuarantorRequest::createFrom(
        Request::create('/', 'POST', $payload, [], $files)
    );
    $formRequest->setContainer(app());
    $formRequest->setRedirector(app('redirect'));

    $validator = Validator::make(array_merge($payload, $files), $formRequest->rules());
    $formRequest->withValidator($validator);

    expect($validator->fails())->toBeFalse($validator->errors()->toJson());

    $formRequest->setValidator($validator);

    return [$formRequest, $payload];
}

test('creating a company guarantor correctly saves the total amount, not zero', function () {
    setGuarantorSetting('guarantee_fee_percent', '2.5');
    ['requester' => $requester] = companyAmountMappingActors();
    Sanctum::actingAs($requester);

    [$formRequest] = validatedCompanyGuarantorFormRequest(50000.0);

    $data = GuarantorData::fromRequest($formRequest);
    $companyData = CompanyDetailData::fromRequest($formRequest);
    $installments = InstallmentData::collectionFromRequest($formRequest);

    $guarantorRequest = app(GuarantorService::class)->createCompany(
        $data,
        $companyData,
        $installments,
        $requester,
        GuarantorUploadData::fromCompanyRequest($formRequest),
    );

    $guarantorRequest->refresh();

    // fees = round(50000 * 2.5 / 100, 2) = 1250 — not the old flat 10
    expect((float) $guarantorRequest->amount)->toBe(50000.0)
        ->and((float) $guarantorRequest->fees)->toBe(1250.0)
        ->and((float) $guarantorRequest->total)->toBe(51250.0)
        ->and($guarantorRequest->title)->toBe('Construction')
        ->and($guarantorRequest->project_type)->toBe('Construction');
});

test('company guarantor total equals sum of its installments', function () {
    ['requester' => $requester] = companyAmountMappingActors();
    Sanctum::actingAs($requester);

    [$formRequest] = validatedCompanyGuarantorFormRequest(50000.0);

    $guarantorRequest = app(GuarantorService::class)->createCompany(
        GuarantorData::fromRequest($formRequest),
        CompanyDetailData::fromRequest($formRequest),
        InstallmentData::collectionFromRequest($formRequest),
        $requester,
        GuarantorUploadData::fromCompanyRequest($formRequest),
    );

    $installmentSum = (float) $guarantorRequest->installments()->sum('amount');

    expect($guarantorRequest->installments)->toHaveCount(2)
        ->and($installmentSum)->toBe(50000.0)
        ->and((float) $guarantorRequest->fresh()->amount)->toBe($installmentSum);
});

test('GuarantorData from company request maps amount from total_amount and title from project_type', function () {
    ['requester' => $requester] = companyAmountMappingActors();
    Sanctum::actingAs($requester);

    [$formRequest] = validatedCompanyGuarantorFormRequest(12500.5);

    $data = GuarantorData::fromRequest($formRequest);

    expect($data->amount)->toBe(12500.5)
        ->and($data->title)->toBe('Construction')
        ->and($data->description)->toBe('')
        ->and($data->project_type)->toBe('Construction')
        ->and($data->counterparty_phone)->toBe(COMPANY_AMOUNT_COUNTERPARTY_PHONE);
});
