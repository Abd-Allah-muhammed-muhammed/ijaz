<?php

use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

test('guarantor:backfill-company-amounts sets amount from installment sum', function () {
    $request = GuarantorRequest::factory()->company()->create([
        'amount' => 0,
        'fees' => 10,
        'title' => '',
        'project_type' => 'Construction',
    ]);

    GuarantorInstallment::factory()->create([
        'guarantor_request_id' => $request->id,
        'order' => 1,
        'amount' => 30000,
    ]);
    GuarantorInstallment::factory()->create([
        'guarantor_request_id' => $request->id,
        'order' => 2,
        'amount' => 20000,
    ]);

    $this->artisan('guarantor:backfill-company-amounts')
        ->assertSuccessful();

    $request->refresh();

    expect((float) $request->amount)->toBe(50000.0)
        ->and((float) $request->total)->toBe(50010.0)
        ->and($request->title)->toBe('Construction')
        ->and($request->type)->toBe(GuarantorTypeEnum::Company);
});

test('guarantor:backfill-company-amounts dry-run does not write', function () {
    $request = GuarantorRequest::factory()->company()->create([
        'amount' => 0,
        'fees' => 10,
        'title' => 'Keep me',
    ]);

    GuarantorInstallment::factory()->create([
        'guarantor_request_id' => $request->id,
        'order' => 1,
        'amount' => 1000,
    ]);

    $this->artisan('guarantor:backfill-company-amounts', ['--dry-run' => true])
        ->assertSuccessful();

    expect((float) $request->fresh()->amount)->toBe(0.0)
        ->and($request->fresh()->title)->toBe('Keep me');
});
