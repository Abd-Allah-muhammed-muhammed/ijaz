<?php

use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;
use Modules\Guarantor\Support\CompanyInstallmentScheduleValidator;
use Modules\Settings\Models\Setting;

function refreshScheduleValidatorSettingsCache(): void
{
    cache()->forget('settings');
    app()->forgetInstance('settings');
}

/**
 * @param  list<array{field: string, message: string}>  $errors
 * @return array<string, list<string>>
 */
function scheduleValidatorErrorsByField(array $errors): array
{
    $grouped = [];

    foreach ($errors as $error) {
        $grouped[$error['field']][] = $error['message'];
    }

    return $grouped;
}

test('CompanyInstallmentScheduleValidator can be unit tested directly, without an HTTP request — pass a raw installments array + total_amount, get back errors', function () {
    $validator = new CompanyInstallmentScheduleValidator;

    $installments = [
        ['order' => 1, 'amount' => 400, 'due_date' => now()->addDays(3)->toDateString()],
        ['order' => 2, 'amount' => 400, 'due_date' => now()->addDays(30)->toDateString()],
    ];

    $errors = $validator->validate($installments, 1000.0);
    $byField = scheduleValidatorErrorsByField($errors);

    expect($errors)->not->toBeEmpty()
        ->and($byField['installments'][0] ?? null)->toBe(__('guarantor.installments_sum_mismatch'));

    $validInstallments = [
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(3)->toDateString()],
        ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
    ];

    expect($validator->validate($validInstallments, 1000.0))->toBe([]);
});

test('the order map is computed once per validation run, not once per rule', function () {
    $validator = new class extends CompanyInstallmentScheduleValidator
    {
        public int $mapBuildCount = 0;

        protected function buildInstallmentOrderMap(array $installments): array
        {
            $this->mapBuildCount++;

            return parent::buildInstallmentOrderMap($installments);
        }
    };

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(3)->toDateString()],
        ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
    ];

    $validator->validate($installments, 1000.0);

    expect($validator->mapBuildCount)->toBe(1);
});

test('an unparseable due_date fails gracefully with a validation error, not an unhandled exception, using the correct narrow exception type', function () {
    $validator = new CompanyInstallmentScheduleValidator;

    $installments = [
        ['order' => 1, 'amount' => 500, 'due_date' => 'totally-not-a-date'],
        ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
    ];

    $errors = $validator->validate($installments, 1000.0);
    $byField = scheduleValidatorErrorsByField($errors);

    expect($errors)->not->toBeEmpty()
        ->and($byField['installments.0.due_date'][0] ?? null)
        ->toBe(__('guarantor.installment_due_date_invalid'));
});

test('StoreCompanyGuarantorRequest no longer contains the 4 private validation methods or buildInstallmentOrderMap — they live only in the new class', function () {
    $privateMethodNames = collect(
        (new ReflectionClass(StoreCompanyGuarantorRequest::class))->getMethods(ReflectionMethod::IS_PRIVATE)
    )->map->getName()->all();

    expect($privateMethodNames)->not->toContain(
        'validateUniqueSequentialOrder',
        'validateInstallmentSum',
        'validateFirstInstallmentMaxDays',
        'validateInstallmentChronologicalOrder',
        'buildInstallmentOrderMap',
    );

    $scheduleValidatorMethods = collect(
        (new ReflectionClass(CompanyInstallmentScheduleValidator::class))->getMethods()
    )->map->getName()->all();

    expect($scheduleValidatorMethods)->toContain('validate', 'buildInstallmentOrderMap');
});

test('CompanyInstallmentScheduleValidator rejects duplicate installment order values', function () {
    $validator = new CompanyInstallmentScheduleValidator;

    $errors = $validator->validate([
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(3)->toDateString()],
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
    ], 1000.0);

    expect(scheduleValidatorErrorsByField($errors)['installments'][0] ?? null)
        ->toBe(__('guarantor.installment_order_duplicate'));
});

test('CompanyInstallmentScheduleValidator skips date rules when order validation already failed', function () {
    $validator = new CompanyInstallmentScheduleValidator;

    $errors = $validator->validate([
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(2)->toDateString()],
    ], 1000.0);

    $byField = scheduleValidatorErrorsByField($errors);

    expect($byField['installments'][0] ?? null)->toBe(__('guarantor.installment_order_duplicate'))
        ->and($byField)->not->toHaveKey('installments.0.due_date')
        ->and($byField)->not->toHaveKey('installments.1.due_date');
});

test('CompanyInstallmentScheduleValidator respects guarantor_first_installment_max_days from settings', function () {
    refreshScheduleValidatorSettingsCache();

    Setting::query()->updateOrCreate(
        ['key' => 'guarantor_first_installment_max_days'],
        ['content' => '3', 'group' => 'guarantor', 'is_public' => false],
    );

    refreshScheduleValidatorSettingsCache();

    $validator = new CompanyInstallmentScheduleValidator;

    $errors = $validator->validate([
        ['order' => 1, 'amount' => 500, 'due_date' => now()->addDays(8)->toDateString()],
        ['order' => 2, 'amount' => 500, 'due_date' => now()->addDays(30)->toDateString()],
    ], 1000.0);

    expect(scheduleValidatorErrorsByField($errors)['installments.0.due_date'][0] ?? null)
        ->toBe(__('guarantor.installment_due_date_first_within_days', ['days' => 3]));
});
