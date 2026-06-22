<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Payment\DTOs\PaymentResponse;

/** @mixin PaymentResponse  */
class PayTapResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->getData();

        return [
            'id' => $this->getTransactionId(),
            'status' => $this->getStatus(),
            'message' => $this->getMessage(),
            'currency' => $data['cart_currency'] ?? null,
            'card' => $data['payment_info'] ?? null,
        ];
    }
}
