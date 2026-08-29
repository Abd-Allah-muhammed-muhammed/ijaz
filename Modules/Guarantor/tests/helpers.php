<?php

use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use Modules\Catalog\Models\Bank;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Settings\Models\Setting;

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
function setupGuarantorActors(string $counterpartyPhone = '0501234567'): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create([
        'phone' => (string) Phone::make($counterpartyPhone),
    ]);
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
        'requester_iban' => 'SA0380000000608010167519',
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
        'iban_certificate' => UploadedFile::fake()->create('iban.pdf', 100, 'application/pdf'),
        'cr_file' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf'),
        'articles_of_association' => UploadedFile::fake()->create('aoa.pdf', 100, 'application/pdf'),
        'national_address_file' => UploadedFile::fake()->create('national-address.pdf', 100, 'application/pdf'),
    ];
}

/**
 * @return array<string, UploadedFile>
 */
function companyGuarantorAcceptFiles(): array
{
    return [
        'signature' => UploadedFile::fake()->create('cp-signature.pdf', 100, 'application/pdf'),
        'iban_certificate' => UploadedFile::fake()->create('cp-iban.pdf', 100, 'application/pdf'),
        'cr_file' => UploadedFile::fake()->create('cp-cr.pdf', 100, 'application/pdf'),
        'articles_of_association' => UploadedFile::fake()->create('cp-aoa.pdf', 100, 'application/pdf'),
        'national_address_file' => UploadedFile::fake()->create('cp-national-address.pdf', 100, 'application/pdf'),
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 */
function attachGuarantorCompanyDetail(GuarantorRequest $guarantorRequest, array $overrides = []): GuarantorCompanyDetail
{
    return GuarantorCompanyDetail::query()->create(array_merge([
        'guarantor_request_id' => $guarantorRequest->id,
        'company_name' => 'Acme Corp',
        'commercial_register' => 'CR-123456',
        'authorized_name' => 'John Doe',
        'authorized_id_number' => '1234567890',
        'authorization_type' => AuthorizationTypeEnum::Owner,
        'requester_account_holder' => 'Requester Name',
        'requester_iban' => 'SA0380000000608010167519',
        'requester_bank_id' => defaultGuarantorTestBankId(),
        'counterparty_account_holder' => 'Counterparty Name',
    ], $overrides));
}

function setGuarantorSetting(string $key, string $content, string $group = 'guarantor', bool $isPublic = true): void
{
    Setting::query()->updateOrCreate(
        ['key' => $key],
        [
            'content' => $content,
            'group' => $group,
            'is_public' => $isPublic,
        ],
    );
    cache()->forget('settings');
    app()->forgetInstance('settings');
}
