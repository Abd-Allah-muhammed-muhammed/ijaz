<?php

test('wallet cannot-update status translation keys exist in all locales', function () {
    foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
        $translations = json_decode(
            file_get_contents(lang_path("{$locale}.json")),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        expect($translations)
            ->toHaveKey('wallet.cannot_update_withdraw_request_status')
            ->and($translations['wallet.cannot_update_withdraw_request_status'])->not->toBeEmpty()
            ->and($translations)->toHaveKey('wallet.cannot_update_top_up_request_status')
            ->and($translations['wallet.cannot_update_top_up_request_status'])->not->toBeEmpty();
    }
});
