<?php

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Http\Resources\Api\GuarantorResource;
use Modules\Guarantor\Http\Resources\Api\StatusHistoryResource;
use Modules\Guarantor\Http\Resources\Dashboard\GuarantorDashboardResource;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Models\GuarantorStatusHistory;

function reasonLabelHistory(array $attributes = []): GuarantorStatusHistory
{
    $guarantorRequest = GuarantorRequest::factory()->create();
    $user = User::factory()->create();

    return GuarantorStatusHistory::query()->create(array_merge([
        'guarantor_request_id' => $guarantorRequest->id,
        'actor_type' => User::class,
        'actor_id' => $user->getKey(),
        'from_status' => GuarantorStatusEnum::Disputed->value,
        'to_status' => GuarantorStatusEnum::EndedViaDispute->value,
    ], $attributes));
}

test('reason_label returns the translated outcome for dispute_resolved_full_requester', function () {
    app()->setLocale('en');

    $history = reasonLabelHistory(['reason' => 'dispute_resolved_full_requester']);

    expect($history->reason_label)->toBe(__('guarantor.dispute_outcome_full_requester'));
});

test('reason_label returns the translated outcome for dispute_resolved_full_counterparty', function () {
    app()->setLocale('en');

    $history = reasonLabelHistory(['reason' => 'dispute_resolved_full_counterparty']);

    expect($history->reason_label)->toBe(__('guarantor.dispute_outcome_full_counterparty'));
});

test('reason_label returns the translated outcome for dispute_escalated_to_court', function () {
    app()->setLocale('en');

    $history = reasonLabelHistory(['reason' => 'dispute_escalated_to_court']);

    expect($history->reason_label)->toBe(__('guarantor.dispute_outcome_escalated'));
});

test('reason_label returns the translated outcome for dispute_closed_by_admin_cancel', function () {
    app()->setLocale('en');

    $history = reasonLabelHistory(['reason' => 'dispute_closed_by_admin_cancel']);

    expect($history->reason_label)->toBe(__('guarantor.dispute_outcome_admin_cancel'));
});

test('reason_label for dispute_resolved_percentage_split:70/30 substitutes the actual percentages into the translated label', function () {
    app()->setLocale('en');

    $history = reasonLabelHistory(['reason' => 'dispute_resolved_percentage_split:70/30']);

    expect($history->reason_label)->toBe(__('guarantor.dispute_outcome_percentage_split_detail', [
        'requester' => '70',
        'counterparty' => '30',
    ]));
});

test('reason_label for a genuine free-text reason returns it unchanged, verbatim', function () {
    app()->setLocale('en');

    $history = reasonLabelHistory(['reason' => 'Goods not as agreed']);

    expect($history->reason_label)->toBe('Goods not as agreed');
});

test('reason_label is null when reason is null', function () {
    $history = reasonLabelHistory(['reason' => null]);

    expect($history->reason_label)->toBeNull();
});

test('reason_label respects the current app locale — same row, different locale, different label text', function () {
    app()->setLocale('en');
    $english = reasonLabelHistory(['reason' => 'dispute_resolved_full_requester'])->reason_label;
    $expectedEnglish = __('guarantor.dispute_outcome_full_requester');

    app()->setLocale('ar');
    $arabic = reasonLabelHistory(['reason' => 'dispute_resolved_full_requester'])->reason_label;
    $expectedArabic = __('guarantor.dispute_outcome_full_requester');

    expect($english)->toBe($expectedEnglish)
        ->and($arabic)->toBe($expectedArabic)
        ->and($english)->not->toBe($arabic);
});

test('StatusHistoryResource exposes reason_label additively — reason itself is completely unchanged', function () {
    app()->setLocale('en');

    $history = reasonLabelHistory(['reason' => 'dispute_resolved_full_requester']);

    $data = StatusHistoryResource::make($history)->toArray(Request::create('/'));

    expect($data)->toHaveKey('reason_label')
        ->and($data['reason'])->toBe('dispute_resolved_full_requester')
        ->and($data['reason_label'])->toBe(__('guarantor.dispute_outcome_full_requester'));
});

test('this is correct on both the mobile GuarantorResource and Dashboard GuarantorDashboardResource paths, since both use the same StatusHistoryResource', function () {
    app()->setLocale('en');

    $guarantorRequest = GuarantorRequest::factory()->create();
    $user = User::factory()->create();

    GuarantorStatusHistory::query()->create([
        'guarantor_request_id' => $guarantorRequest->id,
        'actor_type' => User::class,
        'actor_id' => $user->getKey(),
        'from_status' => GuarantorStatusEnum::Disputed->value,
        'to_status' => GuarantorStatusEnum::Escalated->value,
        'reason' => 'dispute_escalated_to_court',
    ]);

    $guarantorRequest->load(['requester', 'counterparty', 'statusHistories.actor']);

    $mobileHistory = GuarantorResource::make($guarantorRequest)
        ->toArray(Request::create('/'))['status_histories'][0];

    $dashboardHistory = GuarantorDashboardResource::make($guarantorRequest)
        ->toArray(Request::create('/'))['status_histories'][0];

    expect($mobileHistory['reason'])->toBe('dispute_escalated_to_court')
        ->and($mobileHistory['reason_label'])->toBe(__('guarantor.dispute_outcome_escalated'))
        ->and($dashboardHistory['reason'])->toBe('dispute_escalated_to_court')
        ->and($dashboardHistory['reason_label'])->toBe(__('guarantor.dispute_outcome_escalated'));
});
