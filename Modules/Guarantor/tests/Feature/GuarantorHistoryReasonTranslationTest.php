<?php

use Modules\Guarantor\Support\GuarantorDisputeHistoryReason;

test('machine-coded guarantor history reasons have translated labels in all locales', function (string $locale, string $translationKey, array $replacements) {
    app()->setLocale($locale);

    $translated = __($translationKey, $replacements);

    expect($translated)->not->toBe($translationKey)
        ->and($translated)->not->toBeEmpty();
})->with([
    'en full requester' => ['en', 'guarantor.dispute_outcome_full_requester', []],
    'ar full requester' => ['ar', 'guarantor.dispute_outcome_full_requester', []],
    'hi full requester' => ['hi', 'guarantor.dispute_outcome_full_requester', []],
    'ur full requester' => ['ur', 'guarantor.dispute_outcome_full_requester', []],
    'en percentage split detail' => ['en', 'guarantor.dispute_outcome_percentage_split_detail', ['requester' => '70', 'counterparty' => '30']],
    'ar percentage split detail' => ['ar', 'guarantor.dispute_outcome_percentage_split_detail', ['requester' => '70', 'counterparty' => '30']],
    'hi percentage split detail' => ['hi', 'guarantor.dispute_outcome_percentage_split_detail', ['requester' => '70', 'counterparty' => '30']],
    'ur percentage split detail' => ['ur', 'guarantor.dispute_outcome_percentage_split_detail', ['requester' => '70', 'counterparty' => '30']],
]);

test('newly added guarantor validation and dispute keys exist in ar hi and ur', function (string $locale, string $key) {
    app()->setLocale($locale);

    $translated = __("guarantor.{$key}");

    expect($translated)->not->toBe("guarantor.{$key}")
        ->and($translated)->not->toBeEmpty();
})->with([
    ['ar', 'dispute_reason_required'],
    ['hi', 'dispute_reason_required'],
    ['ur', 'dispute_reason_required'],
    ['ar', 'installment_order_duplicate'],
    ['hi', 'installment_order_duplicate'],
    ['ur', 'installment_order_duplicate'],
    ['ar', 'installment_order_not_sequential'],
    ['hi', 'installment_order_not_sequential'],
    ['ur', 'installment_order_not_sequential'],
    ['ar', 'invalid_saudi_iban'],
    ['hi', 'invalid_saudi_iban'],
    ['ur', 'invalid_saudi_iban'],
]);

test('admin cancel during dispute uses a machine reason mapped to a translation key', function () {
    expect(GuarantorDisputeHistoryReason::ClosedByAdminCancel)->toBe('dispute_closed_by_admin_cancel');

    app()->setLocale('en');

    expect(__('guarantor.dispute_outcome_admin_cancel'))->toBe('Closed by admin cancellation during dispute');
});
