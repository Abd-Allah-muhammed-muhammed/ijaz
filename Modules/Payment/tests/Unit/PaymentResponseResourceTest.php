<?php

use Modules\Payment\DTOs\PaymentResponse;
use Modules\Payment\Http\Resources\PaymentResponseResource;

/**
 * Contract freeze for TopUp Show Inertia `paymentResponse` prop shape.
 * Must stay identical after relocating this Resource into Modules/Payment.
 */
test('payment response resource exposes the fixed inertia prop shape', function () {
    $resource = PaymentResponseResource::make(new PaymentResponse(
        status: 'success',
        transactionId: 'TXN-LOCK',
        driver: 'paytabs',
        url: 'https://example.test/pay',
        payable: true,
        data: [
            'cart_currency' => 'SAR',
            'payment_info' => [
                'payment_description' => '4111',
                'card_type' => 'Credit',
            ],
        ],
        message: 'Approved',
    ));

    expect($resource->resolve(request()))->toBe([
        'id' => 'TXN-LOCK',
        'status' => 'success',
        'message' => 'Approved',
        'currency' => 'SAR',
        'card' => [
            'payment_description' => '4111',
            'card_type' => 'Credit',
        ],
    ]);
});
