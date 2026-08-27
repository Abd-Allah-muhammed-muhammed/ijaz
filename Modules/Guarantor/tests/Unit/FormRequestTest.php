<?php

use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Http\Requests\RejectGuarantorRequest;
use Modules\Guarantor\Http\Requests\SendMessageRequest;
use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;
use Modules\Guarantor\Http\Requests\StoreIndividualGuarantorRequest;

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

test('RejectGuarantorRequest requires reason', function () {
    $request = new RejectGuarantorRequest;
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('reason'))->toBeTrue();
});

test('RejectGuarantorRequest accepts a valid reason', function () {
    $request = new RejectGuarantorRequest;
    $validator = Validator::make(
        ['reason' => 'Not acceptable'],
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

test('StoreCompanyGuarantorRequest fails when installment due_date is not after today', function () {
    ['counterparty' => $counterparty] = setupGuarantorActors();

    $data = companyGuarantorPayload([
        'counterparty_phone' => (string) $counterparty->phone,
        'installments' => [
            ['order' => 1, 'amount' => 500, 'due_date' => now()->subDay()->toDateString()],
            ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
        ],
    ]);

    $validator = validateCompanyGuarantorRequest($data, companyGuarantorFiles());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('installments.0.due_date'))->toBeTrue();
});

test('StoreCompanyGuarantorRequest fails when an installment due_date is missing', function () {
    ['counterparty' => $counterparty] = setupGuarantorActors();

    $data = companyGuarantorPayload([
        'counterparty_phone' => (string) $counterparty->phone,
        'installments' => [
            ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
            ['order' => 2, 'amount' => 500],
        ],
    ]);

    $validator = validateCompanyGuarantorRequest($data, companyGuarantorFiles());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('installments.1.due_date'))->toBeTrue()
        ->and($validator->errors()->first('installments.1.due_date'))
        ->toBe(__('guarantor.installment_due_date_required'));
});

test('guarantor installment due_date accepts Arabic-Indic digits', function () {
    $requester = User::factory()->create();
    $counterpartyPhone = '0509988112';
    User::factory()->create([
        'phone' => (string) Phone::make($counterpartyPhone),
    ]);
    Sanctum::actingAs($requester);

    $dueDate1 = now()->addDays(30)->toDateString();
    $dueDate2 = now()->addDays(60)->toDateString();
    $map = [
        '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
        '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
    ];

    $data = companyGuarantorPayload([
        'counterparty_phone' => $counterpartyPhone,
        'installments' => [
            ['order' => 1, 'amount' => 500, 'due_date' => strtr($dueDate1, $map)],
            ['order' => 2, 'amount' => 500, 'due_date' => strtr($dueDate2, $map)],
        ],
    ]);

    $validator = validateCompanyGuarantorRequest($data, companyGuarantorFiles());

    expect($validator->fails())->toBeFalse($validator->errors()->toJson())
        ->and($validator->errors()->has('installments.0.due_date'))->toBeFalse()
        ->and($validator->errors()->has('installments.1.due_date'))->toBeFalse();
});
