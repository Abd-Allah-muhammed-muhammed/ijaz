<?php

namespace Modules\Wallet\DTOs;

use Modules\Payment\Enums\PaymentMethodEnum;

final readonly class CreateTopUpData
{
    public function __construct(
        public float $amount,
        public PaymentMethodEnum $paymentMethod,
        public ?string $paymentDriver,
        public ?string $transactionImage,
        public ?string $userNotes,
    ) {}

    public static function fromRequest(array $validated, ?string $imagePath = null): self
    {
        return new self(
            amount: (float) $validated['amount'],
            paymentMethod: PaymentMethodEnum::from($validated['payment_method']),
            paymentDriver: $validated['payment_driver'] ?? null,
            transactionImage: $imagePath,
            userNotes: $validated['user_notes'] ?? null,
        );
    }
}
