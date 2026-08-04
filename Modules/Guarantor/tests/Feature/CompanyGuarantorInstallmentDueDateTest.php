<?php

use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

const COMPANY_DUE_DATE_COUNTERPARTY_PHONE = '0508877665';

beforeEach(function () {
    Notification::fake();
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
        'authorization_type' => 'power_of_attorney',
        'requester_account_holder' => 'Requester Name',
        'requester_iban' => 'SA1234567890123456789012',
        'counterparty_account_holder' => 'Counterparty Name',
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
        'authorized_id' => UploadedFile::fake()->create('authorized_id.pdf', 100, 'application/pdf'),
        'contracts' => [
            UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
        ],
    ];
}

test('creating a company guarantor with a missing installment due_date is rejected with 422', function () {
    ['requester' => $requester] = companyDueDateActors();
    Sanctum::actingAs($requester);

    $installments = [
        ['order' => 1, 'amount' => 200, 'due_date' => now()->addDays(30)->toDateString()],
        ['order' => 2, 'amount' => 200, 'due_date' => now()->addDays(60)->toDateString()],
        ['order' => 3, 'amount' => 200, 'due_date' => now()->addDays(90)->toDateString()],
        ['order' => 4, 'amount' => 200, 'due_date' => now()->addDays(120)->toDateString()],
        ['order' => 5, 'amount' => 200],
    ];

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

    $dueDates = [
        now()->addDays(30)->toDateString(),
        now()->addDays(60)->toDateString(),
        now()->addDays(90)->toDateString(),
        now()->addDays(120)->toDateString(),
        now()->addDays(150)->toDateString(),
    ];

    $installments = [
        ['order' => 1, 'amount' => 200, 'due_date' => $dueDates[0]],
        ['order' => 2, 'amount' => 200, 'due_date' => $dueDates[1]],
        ['order' => 3, 'amount' => 200, 'due_date' => $dueDates[2]],
        ['order' => 4, 'amount' => 200, 'due_date' => $dueDates[3]],
        ['order' => 5, 'amount' => 200, 'due_date' => $dueDates[4]],
    ];

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

    $installments = [
        ['order' => 1, 'amount' => 200, 'due_date' => now()->addDays(30)->toDateString()],
        ['order' => 2, 'amount' => 200, 'due_date' => now()->addDays(60)->toDateString()],
        ['order' => 3, 'amount' => 200, 'due_date' => now()->addDays(90)->toDateString()],
        ['order' => 4, 'amount' => 200, 'due_date' => now()->addDays(120)->toDateString()],
        ['order' => 5, 'amount' => 200, 'due_date' => null],
    ];

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
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
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
