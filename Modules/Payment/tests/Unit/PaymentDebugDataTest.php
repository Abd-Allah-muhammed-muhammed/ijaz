<?php

use Modules\Payment\DTOs\PaymentDebugData;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Wallet\Models\TopUpRequest;

test('masks sensitive rajhi fields', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'driver' => 'rajhi',
        'status' => PaymentStatusEnum::Accepted,
        'request' => [
            'id' => 'portal-id-12345',
            'password' => 'secret-password-value',
            'trandata' => str_repeat('A', 64),
            'card' => '401200XXXXXX1112',
            'amt' => '100.00',
        ],
        'response' => [
            'trandata' => str_repeat('B', 64),
            'paymentid' => 'pay-123456789',
        ],
    ]);

    $debug = PaymentDebugData::fromPayment($payment);

    expect($debug->request['id'])->toStartWith('por')
        ->and($debug->request['id'])->toContain('***')
        ->and($debug->request['password'])->toStartWith('secr')
        ->and($debug->request['password'])->toContain('***')
        ->and($debug->request['trandata'])->toStartWith('AAAA')
        ->and($debug->request['card'])->toContain('***')
        ->and($debug->request['amt'])->toBe('100.00')
        ->and($debug->response['trandata'])->toStartWith('BBBB')
        ->and($debug->response['paymentid'])->toBe('pay-123456789');
});

test('masks sensitive paytabs fields', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'driver' => 'paytabs',
        'status' => PaymentStatusEnum::Rejected,
        'request' => [
            'profile_id' => '12345',
            'server_key' => 'sk_live_abcdefghijklmnop',
            'cart_id' => 'cart-abc',
        ],
        'response' => [
            'card_number' => '4111111111111111',
            'cvv' => '123',
            'tranRef' => 'TST123456',
        ],
    ]);

    $debug = PaymentDebugData::fromPayment($payment);

    expect($debug->request['profile_id'])->toContain('***')
        ->and($debug->request['server_key'])->toStartWith('sk_l')
        ->and($debug->request['cart_id'])->toBe('cart-abc')
        ->and($debug->response['card_number'])->toContain('***')
        ->and($debug->response['cvv'])->toBe('***')
        ->and($debug->response['tranRef'])->toBe('TST123456');
});

test('does not mask non-sensitive fields', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
        'amount' => 250.50,
        'transaction_id' => 'txn-visible',
        'request' => [
            'driver' => 'testing',
            'amount' => 250.50,
            'payment_id' => 'pay-visible',
        ],
        'response' => [
            'status' => 'success',
            'payment_id' => 'pay-visible',
        ],
    ]);

    $debug = PaymentDebugData::fromPayment($payment);

    expect($debug->driver)->toBe('testing')
        ->and($debug->status)->toBe('accepted')
        ->and($debug->request)->toBe($payment->request)
        ->and($debug->response)->toBe($payment->response)
        ->and($debug->meta['transaction_id'])->toBe('txn-visible')
        ->and($debug->meta['amount'])->toBe(250.50);
});

test('handles null request/response gracefully', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'driver' => 'rajhi',
        'status' => PaymentStatusEnum::Pending,
        'request' => null,
        'response' => null,
    ]);

    $debug = PaymentDebugData::fromPayment($payment);

    expect($debug->request)->toBeNull()
        ->and($debug->response)->toBeNull()
        ->and($debug->meta['id'])->toBe($payment->id);
});

test('masks nested array values recursively', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'driver' => 'rajhi',
        'status' => PaymentStatusEnum::Accepted,
        'response' => [
            'nested' => [
                'card' => '401200XXXXXX1112',
                'safe' => 'visible-value',
            ],
            'items' => [
                ['token' => 'bearer-secret-token'],
                ['label' => 'ok'],
            ],
        ],
    ]);

    $debug = PaymentDebugData::fromPayment($payment);

    expect($debug->response['nested']['card'])->toContain('***')
        ->and($debug->response['nested']['safe'])->toBe('visible-value')
        ->and($debug->response['items'][0]['token'])->toStartWith('bear')
        ->and($debug->response['items'][1]['label'])->toBe('ok');
});
