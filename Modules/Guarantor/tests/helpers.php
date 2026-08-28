<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use Modules\Catalog\Models\Bank;
use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;

function activeGuarantorTestBank(?array $translations = null): Bank
{
    return Bank::factory()->create([
        'translations' => $translations ?? geoNameTranslations('Test Bank'),
    ]);
}

function defaultGuarantorTestBankId(): int
{
    return activeGuarantorTestBank()->id;
}

/**
 * @return array{requester: User, counterparty: User}
 */
function setupGuarantorActors(): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create();
    Sanctum::actingAs($requester);

    return compact('requester', 'counterparty');
}

/**
 * @param  array<string, mixed>  $data
 * @param  array<string, mixed>  $files
 */
function validateCompanyGuarantorRequest(array $data, array $files = []): Illuminate\Validation\Validator
{
    $formRequest = StoreCompanyGuarantorRequest::createFrom(
        Request::create('/', 'POST', $data, [], $files)
    );
    $formRequest->setContainer(app());
    $formRequest->setRedirector(app('redirect'));

    $prepare = new ReflectionMethod($formRequest, 'prepareForValidation');
    $prepare->invoke($formRequest);

    $validator = Validator::make(
        array_merge($formRequest->all(), $files),
        $formRequest->rules(),
        $formRequest->messages(),
        $formRequest->attributes()
    );
    $formRequest->withValidator($validator);

    return $validator;
}

/**
 * @return array<string, mixed>
 */
function companyGuarantorPayload(array $overrides = []): array
{
    return array_merge([
        'counterparty_phone' => '0501234567',
        'project_type' => 'Construction',
        'total_amount' => 1000,
        'installments' => [
            ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(3)->toDateString()],
            ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
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
    ], $overrides);
}

/**
 * @return array<string, UploadedFile>
 */
function companyGuarantorFiles(): array
{
    return [
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
        'authorized_id' => UploadedFile::fake()->create('authorized_id.pdf', 100, 'application/pdf'),
        'contracts' => [
            UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
        ],
    ];
}
