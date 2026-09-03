<?php

it('defines validation.regex and validation.invalid_saudi_iban in every locale validation source', function () {
    foreach (['en', 'ar', 'hi', 'ur'] as $locale) {
        $path = lang_path("{$locale}/validation.php");
        expect($path)->toBeFile();

        /** @var array<string, mixed> $messages */
        $messages = require $path;

        expect($messages)->toHaveKeys(['regex', 'invalid_saudi_iban'])
            ->and($messages['regex'])->toBeString()->not->toBeEmpty()
            ->and($messages['invalid_saudi_iban'])->toBeString()->not->toBeEmpty();
    }
});

it('generates frontend validation.regex and validation.invalid_saudi_iban for every locale', function () {
    $this->artisan('make:js-translations')->assertSuccessful();

    foreach (['en', 'ar', 'hi', 'ur'] as $locale) {
        $path = resource_path("js/lang/{$locale}.json");
        expect($path)->toBeFile();

        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($path), true);

        expect($data['validation']['regex'])->toBeString()->not->toBeEmpty()
            ->and($data['validation']['invalid_saudi_iban'])->toBeString()->not->toBeEmpty();
    }
});
