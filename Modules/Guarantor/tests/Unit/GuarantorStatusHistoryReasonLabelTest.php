<?php

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Http\Resources\Api\GuarantorResource;
use Modules\Guarantor\Http\Resources\Api\StatusHistoryResource;
use Modules\Guarantor\Http\Resources\Dashboard\GuarantorDashboardResource;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Models\GuarantorStatusHistory;

function statusHistoryWithReason(array $attributes = []): GuarantorStatusHistory
{
    $guarantorRequest = GuarantorRequest::factory()->create();
    $user = User::factory()->create();

    return GuarantorStatusHistory::query()->create(array_merge([
        'guarantor_request_id' => $guarantorRequest->id,
        'actor_type' => User::class,
        'actor_id' => $user->getKey(),
        'from_status' => GuarantorStatusEnum::Disputed->value,
        'to_status' => GuarantorStatusEnum::Escalated->value,
    ], $attributes));
}

test('reason is exposed as an object with value and label for a known machine code, e.g. {"value": "dispute_escalated_to_court", "label": "Escalated to court"}', function () {
    app()->setLocale('en');

    $history = statusHistoryWithReason(['reason' => 'dispute_escalated_to_court']);

    expect($history->reason)->toBe([
        'value' => 'dispute_escalated_to_court',
        'label' => __('guarantor.dispute_outcome_escalated'),
    ]);
});

test('reason.value equals reason.label exactly for genuine free-text reasons', function () {
    app()->setLocale('en');

    $history = statusHistoryWithReason(['reason' => 'Goods not as agreed']);

    expect($history->reason)->toBe([
        'value' => 'Goods not as agreed',
        'label' => 'Goods not as agreed',
    ]);
});

test('reason is null (not an object) when the history row has no reason, matching from_status\'s existing null pattern', function () {
    $history = statusHistoryWithReason(['reason' => null]);

    expect($history->reason)->toBeNull();
});

test('reason.label for percentage_split substitutes the correct percentages, reason.value retains the raw unparsed code with the ratio', function () {
    app()->setLocale('en');

    $history = statusHistoryWithReason(['reason' => 'dispute_resolved_percentage_split:70/30']);

    expect($history->reason)->toBe([
        'value' => 'dispute_resolved_percentage_split:70/30',
        'label' => __('guarantor.dispute_outcome_percentage_split_detail', [
            'requester' => '70',
            'counterparty' => '30',
        ]),
    ]);
});

test('reason.label respects Accept-Language / current locale; reason.value never changes with locale', function () {
    app()->setLocale('en');
    $english = statusHistoryWithReason(['reason' => 'dispute_resolved_full_requester'])->reason;
    $expectedEnglishLabel = __('guarantor.dispute_outcome_full_requester');

    app()->setLocale('ar');
    $arabic = statusHistoryWithReason(['reason' => 'dispute_resolved_full_requester'])->reason;
    $expectedArabicLabel = __('guarantor.dispute_outcome_full_requester');

    expect($english['value'])->toBe('dispute_resolved_full_requester')
        ->and($arabic['value'])->toBe('dispute_resolved_full_requester')
        ->and($english['label'])->toBe($expectedEnglishLabel)
        ->and($arabic['label'])->toBe($expectedArabicLabel)
        ->and($english['label'])->not->toBe($arabic['label']);
});

test('flat reason_label field no longer exists on the resource — fully replaced by the reason object', function () {
    app()->setLocale('en');

    $history = statusHistoryWithReason(['reason' => 'dispute_resolved_full_requester']);

    $data = StatusHistoryResource::make($history)->toArray(Request::create('/'));

    expect($data)->not->toHaveKey('reason_label')
        ->and($data['reason'])->toBe([
            'value' => 'dispute_resolved_full_requester',
            'label' => __('guarantor.dispute_outcome_full_requester'),
        ]);
});

test('this is correct on both the mobile GuarantorResource and Dashboard GuarantorDashboardResource paths, since both use the same StatusHistoryResource', function () {
    app()->setLocale('en');

    $guarantorRequest = GuarantorRequest::factory()->create();
    $user = User::factory()->create();

    $history = GuarantorStatusHistory::query()->create([
        'guarantor_request_id' => $guarantorRequest->id,
        'actor_type' => User::class,
        'actor_id' => $user->getKey(),
        'from_status' => GuarantorStatusEnum::Disputed->value,
        'to_status' => GuarantorStatusEnum::Escalated->value,
        'reason' => 'dispute_escalated_to_court',
    ]);

    $guarantorRequest->load(['requester', 'counterparty', 'statusHistories.actor']);

    $expectedReason = [
        'value' => 'dispute_escalated_to_court',
        'label' => __('guarantor.dispute_outcome_escalated'),
    ];

    $serialized = StatusHistoryResource::make($history)->toArray(Request::create('/'));

    $mobileHistory = GuarantorResource::make($guarantorRequest)
        ->toArray(Request::create('/'))['status_histories'][0];

    $dashboardHistory = GuarantorDashboardResource::make($guarantorRequest)
        ->toArray(Request::create('/'))['status_histories'][0];

    expect($serialized['reason'])->toBe($expectedReason)
        ->and($serialized)->not->toHaveKey('reason_label')
        ->and($mobileHistory['reason'])->toBe($expectedReason)
        ->and($dashboardHistory['reason'])->toBe($expectedReason);
});
