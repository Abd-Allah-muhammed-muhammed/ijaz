<?php

use Modules\Guarantor\Exceptions\GuarantorException;

test('GuarantorException render returns translated json response', function () {
    $exception = new GuarantorException('guarantor.not_found', 404);

    $response = $exception->render();

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true))->toMatchArray([
            'success' => false,
            'message' => __('guarantor.not_found'),
            'data' => [],
            'errors' => [],
        ]);
});

test('GuarantorException exposes translation key and status code', function () {
    $exception = new GuarantorException('guarantor.status_transition_not_allowed', 422);

    expect($exception->getTranslationKey())->toBe('guarantor.status_transition_not_allowed')
        ->and($exception->getHttpStatusCode())->toBe(422);
});
