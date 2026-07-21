<?php

test('support module is registered', function () {
    expect(array_key_exists('Support', json_decode(file_get_contents(base_path('modules_statuses.json')), true)))->toBeTrue();
});
