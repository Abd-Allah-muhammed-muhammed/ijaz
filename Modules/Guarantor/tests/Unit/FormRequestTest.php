<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Http\Requests\SendMessageRequest;
use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;
use Modules\Guarantor\Http\Requests\StoreIndividualGuarantorRequest;
use Modules\Guarantor\Http\Requests\UpdateGuarantorStatusRequest;

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

    $validator = Validator::make(
        array_merge($data, $files),
        $formRequest->rules()
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
        'total_amount' => 1000,
        'installments' => [
            ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
            ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(60)->toDateString()],
        ],
        'company_name' => 'Acme Corp',
        'commercial_register' => 'CR-123456',
        'authorized_name' => 'John Doe',
        'authorized_id_number' => '1234567890',
        'authorization_type' => 'power_of_attorney',
        'requester_account_holder' => 'Requester Name',
        'requester_iban' => 'SA1234567890123456789012',
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

test('StoreIndividualGuarantorRequest requires title, description, amount, phone, signature', function () {
    $request = new StoreIndividualGuarantorRequest;
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('title'))->toBeTrue()
        ->and($validator->errors()->has('description'))->toBeTrue()
        ->and($validator->errors()->has('amount'))->toBeTrue()
        ->and($validator->errors()->has('counterparty_phone'))->toBeTrue()
        ->and($validator->errors()->has('signature'))->toBeTrue();
});

test('StoreIndividualGuarantorRequest fails with amount zero', function () {
    $request = new StoreIndividualGuarantorRequest;
    $rules = $request->rules();

    $validator = Validator::make(['amount' => 0], ['amount' => $rules['amount']]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('amount'))->toBeTrue();
});

test('StoreCompanyGuarantorRequest requires installments', function () {
    ['counterparty' => $counterparty] = setupGuarantorActors();

    $request = new StoreCompanyGuarantorRequest;
    $payload = companyGuarantorPayload([
        'counterparty_phone' => (string) $counterparty->phone,
    ]);
    unset($payload['installments']);

    $validator = Validator::make($payload, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('installments'))->toBeTrue();
});

test('StoreCompanyGuarantorRequest fails when installments sum != total_amount', function () {
    ['counterparty' => $counterparty] = setupGuarantorActors();

    $data = companyGuarantorPayload([
        'counterparty_phone' => (string) $counterparty->phone,
        'total_amount' => 1000,
        'installments' => [
            ['order' => 1, 'amount' => 400, 'due_date' => now()->addDays(30)->toDateString()],
            ['order' => 2, 'amount' => 400, 'due_date' => now()->addDays(60)->toDateString()],
        ],
    ]);

    $validator = validateCompanyGuarantorRequest($data, companyGuarantorFiles());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('installments'))->toBeTrue()
        ->and($validator->errors()->first('installments'))->toBe(__('guarantor.installments_sum_mismatch'));
});

test('StoreCompanyGuarantorRequest requires at least one contract file', function () {
    ['counterparty' => $counterparty] = setupGuarantorActors();

    $files = companyGuarantorFiles();
    unset($files['contracts']);

    $validator = validateCompanyGuarantorRequest(
        companyGuarantorPayload(['counterparty_phone' => (string) $counterparty->phone]),
        $files
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('contracts'))->toBeTrue();
});

test('UpdateGuarantorStatusRequest requires reason when status is rejected', function () {
    $request = UpdateGuarantorStatusRequest::createFrom(
        Request::create('/', 'POST', ['status' => GuarantorStatusEnum::Rejected->value])
    );
    $request->setContainer(app());

    $validator = Validator::make(
        ['status' => GuarantorStatusEnum::Rejected->value],
        $request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('reason'))->toBeTrue();
});

test('UpdateGuarantorStatusRequest requires reason when status is cancelled', function () {
    $request = UpdateGuarantorStatusRequest::createFrom(
        Request::create('/', 'POST', ['status' => GuarantorStatusEnum::Cancelled->value])
    );
    $request->setContainer(app());

    $validator = Validator::make(
        ['status' => GuarantorStatusEnum::Cancelled->value],
        $request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('reason'))->toBeTrue();
});

test('UpdateGuarantorStatusRequest does not require reason when status is approved', function () {
    $request = UpdateGuarantorStatusRequest::createFrom(
        Request::create('/', 'POST', ['status' => GuarantorStatusEnum::Approved->value])
    );
    $request->setContainer(app());

    $validator = Validator::make(
        ['status' => GuarantorStatusEnum::Approved->value],
        $request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('SendMessageRequest requires content or files', function () {
    $request = new SendMessageRequest;
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('content'))->toBeTrue()
        ->and($validator->errors()->has('files'))->toBeTrue();
});
