<?php

use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

const VALIDATION_HARDENING_COUNTERPARTY_PHONE = '0507766554';
const VALID_SAUDI_IBAN = 'SA0380000000608010167519';

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{requester: User, counterparty: User}
 */
function validationHardeningActors(): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create([
        'phone' => (string) Phone::make(VALIDATION_HARDENING_COUNTERPARTY_PHONE),
    ]);

    return compact('requester', 'counterparty');
}

/**
 * @param  list<array{order: int, amount: float|int, due_date?: string}>  $installments
 * @return array<string, mixed>
 */
function validationHardeningCompanyPayload(array $installments, array $overrides = []): array
{
    $totalAmount = collect($installments)->sum('amount');

    return array_merge([
        'counterparty_phone' => VALIDATION_HARDENING_COUNTERPARTY_PHONE,
        'project_type' => 'Construction',
        'total_amount' => $totalAmount,
        'installments' => $installments,
        'company_name' => 'Acme Corp',
        'commercial_register' => 'CR-123456',
        'authorized_name' => 'John Doe',
        'authorized_id_number' => '1234567890',
        'authorization_type' => 'power_of_attorney',
        'requester_account_holder' => 'Requester Name',
        'requester_iban' => VALID_SAUDI_IBAN,
        'requester_bank_id' => defaultGuarantorTestBankId(),
        'counterparty_account_holder' => 'Counterparty Name',
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
        'authorized_id' => UploadedFile::fake()->create('authorized_id.pdf', 100, 'application/pdf'),
        'contracts' => [
            UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
        ],
    ], $overrides);
}

test('a Saudi IBAN in an invalid format is rejected with a clear validation message on company create', function () {
    ['requester' => $requester] = validationHardeningActors();
    Sanctum::actingAs($requester);

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(3)->toDateString()],
        ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
    ];

    $response = $this->postJson(
        route('api.v1.guarantor.guarantor.store.company'),
        validationHardeningCompanyPayload($installments, [
            'requester_iban' => 'NOT-A-VALID-IBAN',
        ]),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['requester_iban']);

    expect($response->json('errors.requester_iban.0'))->toBe(__('guarantor.invalid_saudi_iban'));
});

test('a validly-formatted Saudi IBAN passes validation', function () {
    ['requester' => $requester] = validationHardeningActors();
    Sanctum::actingAs($requester);

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(3)->toDateString()],
        ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
    ];

    $response = $this->postJson(
        route('api.v1.guarantor.guarantor.store.company'),
        validationHardeningCompanyPayload($installments, [
            'requester_iban' => VALID_SAUDI_IBAN,
            'counterparty_iban' => 'sa03 8000 0000 6080 1016 7519',
        ]),
    );

    $response->assertSuccessful();
});

test('duplicate installment order values in the same company create request return a clean 422 with a field-level error, not a raw database exception', function () {
    ['requester' => $requester] = validationHardeningActors();
    Sanctum::actingAs($requester);

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(60)->toDateString()],
    ];

    $response = $this->postJson(
        route('api.v1.guarantor.guarantor.store.company'),
        validationHardeningCompanyPayload($installments),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['installments']);

    expect($response->json('errors.installments.0'))->toBe(__('guarantor.installment_order_duplicate'))
        ->and(GuarantorRequest::query()->count())->toBe(0);
});

test('installment order values must be sequential/unique — gaps or duplicates are rejected before reaching the database', function (array $installments, string $expectedKey) {
    ['requester' => $requester] = validationHardeningActors();
    Sanctum::actingAs($requester);

    $response = $this->postJson(
        route('api.v1.guarantor.guarantor.store.company'),
        validationHardeningCompanyPayload($installments),
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['installments']);

    expect($response->json('errors.installments.0'))->toBe(__("guarantor.{$expectedKey}"))
        ->and(GuarantorRequest::query()->count())->toBe(0);
})->with([
    'duplicate orders' => [
        [
            ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
            ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(60)->toDateString()],
        ],
        'installment_order_duplicate',
    ],
    'gap in sequence' => [
        [
            ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
            ['order' => 3, 'amount' => 500, 'due_date' => now()->addDays(60)->toDateString()],
        ],
        'installment_order_not_sequential',
    ],
    'does not start at 1' => [
        [
            ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
            ['order' => 3, 'amount' => 500, 'due_date' => now()->addDays(60)->toDateString()],
        ],
        'installment_order_not_sequential',
    ],
]);

test('updating a Company guarantor amount while PendingAdmin is rejected if it no longer matches the sum of existing installments', function () {
    ['requester' => $requester] = validationHardeningActors();
    Sanctum::actingAs($requester);

    $guarantorRequest = GuarantorRequest::factory()->company()->pendingAdmin()->create([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'amount' => 1000,
    ]);

    GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 600,
    ]);
    GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 400,
    ]);

    $this->postJson(route('api.v1.guarantor.guarantor.update', $guarantorRequest), [
        'amount' => 999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount'])
        ->assertJsonPath('errors.amount.0', __('guarantor.installments_sum_mismatch'));
});

test('updating amount is still allowed when it correctly matches installment sum, or when the request is Individual (no installment concept) — regression', function () {
    ['requester' => $requester] = validationHardeningActors();
    Sanctum::actingAs($requester);

    $companyRequest = GuarantorRequest::factory()->company()->pendingAdmin()->create([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'amount' => 1000,
    ]);
    GuarantorInstallment::factory()->for($companyRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 600,
    ]);
    GuarantorInstallment::factory()->for($companyRequest, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 400,
    ]);

    $this->postJson(route('api.v1.guarantor.guarantor.update', $companyRequest), [
        'amount' => 1000,
    ])->assertSuccessful();

    $individualRequest = GuarantorRequest::factory()->pendingAdmin()->create([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'type' => GuarantorTypeEnum::Individual,
        'amount' => 500,
    ]);

    $this->postJson(route('api.v1.guarantor.guarantor.update', $individualRequest), [
        'amount' => 750,
    ])->assertSuccessful()
        ->assertJsonPath('data.amount', '750.00');
});
