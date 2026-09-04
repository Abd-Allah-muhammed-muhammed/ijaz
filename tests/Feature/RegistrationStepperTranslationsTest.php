<?php

/**
 * Provider registration stepper sidebar labels + success copy must resolve
 * in every supported locale (not fall back to the raw key).
 */
test('registration stepper and success translation keys resolve in all locales', function (string $locale): void {
    $keys = [
        'registration_step_label',
        'registration_step_of',
        'account_type',
        'account_information',
        'categories',
        'files',
        'summary',
        'phone_verification',
        'completed',
        'registration_complete_title',
        'registration_complete_body',
        'registration_next_wait_notification',
        'registration_next_watch_updates',
        'registration_next_prepare_services',
        'registration_summary_heading',
        'registration_date',
        'return_to_home_page',
        'about_placeholder',
        'account_type_billing_tooltip',
        'select_account_type',
        'setup_your_account_information',
        'select_your_categories & skills',
        'provide_your_files',
        'review_your_information',
        'setup_your_phone_verification',
        'your_account_is_created',
    ];

    foreach ($keys as $key) {
        expect(__($key, [], $locale))
            ->not->toBe($key, "Missing translation for [{$key}] in locale [{$locale}]");
    }

    $numbered = __('registration_step_label', [
        'number' => 1,
        'title' => __('account_type', [], $locale),
    ], $locale);

    expect($numbered)->toContain('1')
        ->and($numbered)->toContain(__('account_type', [], $locale))
        ->and($numbered)->not->toContain(':number')
        ->and($numbered)->not->toContain(':title');
})->with(['en', 'ar', 'hi', 'ur']);
