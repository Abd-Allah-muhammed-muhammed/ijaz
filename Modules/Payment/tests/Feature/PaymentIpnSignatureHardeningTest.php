<?php

test('paymentIPN without signature header returns 400 not 500', function () {
    $this->post('/paymentIPN', [])
        ->assertStatus(400)
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

test('paymentIPN with empty signature header returns 400 not 500', function () {
    $this->post('/paymentIPN', [], [
        'HTTP_SIGNATURE' => '',
    ])->assertStatus(400);
});

test('paymentIPN with invalid signature header returns 400 not 500', function () {
    $this->call(
        'POST',
        '/paymentIPN',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SIGNATURE' => 'not-a-valid-paytabs-signature',
        ],
        '{"cart_id":"x","tran_ref":"y"}',
    )->assertStatus(400);
});
