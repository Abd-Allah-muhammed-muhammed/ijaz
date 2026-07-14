<?php

test('sms module is registered', function () {
    expect(array_key_exists('Sms', json_decode(file_get_contents(base_path('modules_statuses.json')), true)))->toBeTrue();
});
