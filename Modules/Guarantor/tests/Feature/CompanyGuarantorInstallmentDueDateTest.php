<?php

use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Settings\Models\Setting;

const COMPANY_DUE_DATE_COUNTERPARTY_PHONE = '0508877665';

beforeEach(function () {
    Notification::fake();
    refreshGuarantorSettingsCache();
});

/**
 * @return array{requester: User, counterparty: User}
 */
function companyDueDateActors(): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create([
        'phone' => (string) Phone::make(COMPANY_DUE_DATE_COUNTERPARTY_PHONE),
    ]);

    return compact('requester', 'counterparty');
}

function refreshGuarantorSettingsCache(): void
{
    cache()->forget('settings');
    app()->forgetInstance('settings');
}

function setGuarantorFirstInstallmentMaxDays(int $days): void
{
    refreshGuarantorSettingsCache();

    Setting::query()->updateOrCreate(
        ['key' => 'guarantor_first_installment_max_days'],
        ['content' => (string) $days, 'group' => 'guarantor', 'is_public' => false],
    );

    refreshGuarantorSettingsCache();
}

/**
 * @param  list<array{order: int, amount: float|int, due_date?: string|null}>  $installments
 * @return array<string, mixed>
 */
function companyDueDatePayload(array $installments, float $totalAmount): array
{
    return [
        'counterparty_phone' => COMPANY_DUE_DATE_COUNTERPARTY_PHONE,
        'project_type' => 'Construction',
        'total_amount' => $totalAmount,
        'installments' => $installments,
        'company_name' => 'Acme Corp',
        'commercial_register' => 'CR-123456',
        'authorized_name' => 'John Doe',
        'authorized_id_number' => '1234567890',
        'authorization_type' => 'owner',
        'requester_account_holder' => 'Requester Name',
        'requester_iban' => 'SA1234567890123456789012',
        'requester_bank_id' => defaultGuarantorTestBankId(),
        'counterparty_account_holder' => 'Counterparty Name',
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
 * @return list<array{order: int, amount: int, due_date: string}>
 */
function validFiveInstallmentSchedule(): array
{
    return [
        ['order' => 1, 'amount' => 200, 'due_date' => now()->addDays(3)->toDateString()],
        ['order' => 2, 'amount' => 200, 'due_date' => now()->addDays(30)->toDateString()],
        ['order' => 3, 'amount' => 200, 'due_date' => now()->addDays(60)->toDateString()],
        ['order' => 4, 'amount' => 200, 'due_date' => now()->addDays(90)->toDateString()],
        ['order' => 5, 'amount' => 200, 'due_date' => now()->addDays(120)->toDateString()],
    ];
}

test('creating a company guarantor with a missing installment due_date is rejected with 422', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    $installments = validFiveInstallmentSchedule();
    unset($installments[4]['due_date']);

    $response = $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['installments.4.due_date']);

    $errors = $response->json('errors');

    expect($errors['installments.4.due_date'][0] ?? null)
        ->toBe(__('guarantor.installment_due_date_required'));
});

test('creating a company guarantor with all installment due_dates present succeeds', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    $installments = validFiveInstallmentSchedule();
    $dueDates = collect($installments)->pluck('due_date')->all();

    $response = $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    );

    $response->assertSuccessful();

    $guarantorRequest = GuarantorRequest::query()->latest('created_at')->first();

    expect($guarantorRequest)->not->toBeNull()
        ->and($guarantorRequest->installments)->toHaveCount(5)
        ->and($guarantorRequest->installments->pluck('due_date')->map->toDateString()->all())
        ->toBe($dueDates);
});

test('no fallback date is ever written to the database for a missing due_date', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    $installmentCountBefore = GuarantorInstallment::query()->count();
    $guarantorCountBefore = GuarantorRequest::query()->count();

    $installments = validFiveInstallmentSchedule();
    $installments[4]['due_date'] = null;

    $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    )->assertUnprocessable()
        ->assertJsonValidationErrors(['installments.4.due_date']);

    expect(GuarantorRequest::query()->count())->toBe($guarantorCountBefore)
        ->and(GuarantorInstallment::query()->count())->toBe($installmentCountBefore)
        ->and(
            GuarantorInstallment::query()
                ->whereDate('due_date', '2027-01-01')
                ->exists()
        )->toBeFalse();
});

test('empty string installment due_date is rejected with 422 and writes nothing', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    $installmentCountBefore = GuarantorInstallment::query()->count();

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(3)->toDateString()],
        ['order' => 2, 'amount' => 500, 'due_date' => ''],
    ];

    $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    )->assertUnprocessable()
        ->assertJsonValidationErrors(['installments.1.due_date']);

    expect(GuarantorInstallment::query()->count())->toBe($installmentCountBefore)
        ->and(
            GuarantorInstallment::query()
                ->whereDate('due_date', '2027-01-01')
                ->exists()
        )->toBeFalse();
});

test('installment #1 due_date within the configured max-days window passes', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(5)->toDateString()],
        ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
    ];

    $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    )->assertSuccessful();
});

test('installment #1 due_date beyond the configured max-days window is rejected, with the actual configured day count in the error message', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    setGuarantorFirstInstallmentMaxDays(5);

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(6)->toDateString()],
        ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
    ];

    $response = $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['installments.0.due_date']);

    $errors = $response->json('errors');

    expect($errors['installments.0.due_date'][0] ?? null)
        ->toBe(__('guarantor.installment_due_date_first_within_days', ['days' => 5]));
});

test('installment #1 due_date still must be after today — regression', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => now()->toDateString()],
        ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
    ];

    $response = $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['installments.0.due_date']);

    $errors = $response->json('errors');

    expect($errors['installments.0.due_date'][0] ?? null)
        ->toBe(__('guarantor.installment_due_date_after_today'));
});

test('installment N due_date >= installment N-1 due_date (by order, not array position) passes', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    $installments = [
        ['order' => 3, 'amount' => 334, 'due_date' => now()->addDays(60)->toDateString()],
        ['order' => 1, 'amount' => 333, 'due_date' => now()->addDays(3)->toDateString()],
        ['order' => 2, 'amount' => 333, 'due_date' => now()->addDays(30)->toDateString()],
    ];

    $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    )->assertSuccessful();
});

test('installment N due_date < installment N-1 due_date is rejected', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(3)->toDateString()],
        ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(10)->toDateString()],
    ];

    $installments[1]['due_date'] = now()->addDays(2)->toDateString();

    $response = $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['installments.1.due_date']);

    $errors = $response->json('errors');

    expect($errors['installments.1.due_date'][0] ?? null)
        ->toBe(__('guarantor.installment_due_date_before_previous', ['order' => 2]));
});

test('installment N due_date equal to N-1 due_date is allowed', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    $sharedDate = now()->addDays(4)->toDateString();

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => $sharedDate],
        ['order' => 2, 'amount' => 500, 'due_date' => $sharedDate],
    ];

    $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    )->assertSuccessful();
});

test('submitting installments out of array order still validates correctly against the true order-based sequence, not array position', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    $installments = [
        ['order' => 3, 'amount' => 334, 'due_date' => now()->addDays(10)->toDateString()],
        ['order' => 1, 'amount' => 333, 'due_date' => now()->addDays(3)->toDateString()],
        ['order' => 2, 'amount' => 333, 'due_date' => now()->addDays(30)->toDateString()],
    ];

    $response = $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['installments.0.due_date']);

    $errors = $response->json('errors');

    expect($errors['installments.0.due_date'][0] ?? null)
        ->toBe(__('guarantor.installment_due_date_before_previous', ['order' => 3]));
});

test('the due-date checks do not run when order-uniqueness/sequential validation has already failed for this request', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(2)->toDateString()],
    ];

    $response = $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['installments']);

    $errors = $response->json('errors');

    expect($errors['installments'][0] ?? null)->toBe(__('guarantor.installment_order_duplicate'))
        ->and($errors)->not->toHaveKey('installments.0.due_date')
        ->and($errors)->not->toHaveKey('installments.1.due_date');
});

test('changing the settings value changes both the validation boundary and the error message wording, without a code deploy', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    setGuarantorFirstInstallmentMaxDays(10);

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(8)->toDateString()],
        ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
    ];

    $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    )->assertSuccessful();

    setGuarantorFirstInstallmentMaxDays(3);

    $response = $this->post(
        route('api.v1.guarantor.guarantor.store.company'),
        companyDueDatePayload($installments, 1000),
        ['Accept' => 'application/json'],
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['installments.0.due_date']);

    $errors = $response->json('errors');

    expect($errors['installments.0.due_date'][0] ?? null)
        ->toBe(__('guarantor.installment_due_date_first_within_days', ['days' => 3]));
});
