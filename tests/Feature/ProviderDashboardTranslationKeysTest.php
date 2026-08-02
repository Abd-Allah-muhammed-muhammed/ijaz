<?php

/**
 * Guards against the Provider Dashboard i18n regressions reported across Admin/Provider
 * sidebars, Wallet UI, Top-up Requests/Details, Chat search, and recharge validation.
 */
it('defines critical Provider Dashboard translation keys in every locale JSON source', function () {
    $locales = ['en', 'ar', 'ur', 'hi'];

    $keys = [
        // Admin sidebar (Urdu/Hindi gaps + banners across all locales)
        'banners',
        'questions',
        'tickets',
        'opportunities',
        'top_up_requests',

        // Provider sidebar
        'wallet',
        'offers',
        'communications',
        'finance',

        // Wallet UI
        'recharge',
        'withdraw',
        'close',
        'date',
        'balance_after',
        'balance_before',
        'operation',
        'reference_number',
        'online',
        'offline',
        'payment_method',
        'payment_status',
        'payment_driver',
        'transaction_image',

        // Chat + toasts
        'search_by_phone',
        'Top up request created successfully and is pending admin approval.',
        'Payment Successful',
        'Payment Failed, Please Try Again',
    ];

    foreach ($locales as $locale) {
        $path = lang_path("{$locale}.json");
        expect(file_exists($path))->toBeTrue();

        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($path), true);

        foreach ($keys as $key) {
            expect(array_key_exists($key, $data))->toBeTrue("Missing [{$key}] in lang/{$locale}.json")
                ->and($data[$key])->toBeString()
                ->and($data[$key])->not->toBeEmpty();
        }
    }
});

it('defines validation gt and mimes messages used by Provider wallet/offer schemas', function () {
    foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
        $messages = require lang_path("{$locale}/validation.php");

        expect($messages['required'])->toBeString()->not->toBeEmpty()
            ->and($messages['mimes'])->toBeString()->not->toBeEmpty()
            ->and($messages['gt']['numeric'])->toBeString()->not->toBeEmpty()
            ->and($messages['min']['numeric'])->toBeString()->not->toBeEmpty()
            ->and($messages['max']['string'])->toBeString()->not->toBeEmpty();
    }
});

it('generated frontend translations include Provider Dashboard keys and validation nesting', function () {
    $this->artisan('make:js-translations')->assertSuccessful();

    foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
        $path = resource_path("js/lang/{$locale}.json");
        expect(file_exists($path))->toBeTrue();

        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($path), true);

        expect($data['wallet'])->toBeString()->not->toBeEmpty()
            ->and($data['search_by_phone'])->toBeString()->not->toBeEmpty()
            ->and($data['payment_method'])->toBeString()->not->toBeEmpty()
            ->and($data['payment_status'])->toBeString()->not->toBeEmpty()
            ->and($data['banners'])->toBeString()->not->toBeEmpty()
            ->and($data['Top up request created successfully and is pending admin approval.'])->toBeString()->not->toBeEmpty()
            ->and($data['validation']['required'])->toBeString()->not->toBeEmpty()
            ->and($data['validation']['mimes'])->toBeString()->not->toBeEmpty()
            ->and($data['validation']['gt']['numeric'])->toBeString()->not->toBeEmpty();
    }
});
