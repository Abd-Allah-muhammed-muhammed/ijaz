<?php

namespace Modules\Orders\DTOs;

use Modules\Orders\Enums\OfferStatusEnum;

final readonly class UpdateOfferStatusDTO
{
    public function __construct(
        public OfferStatusEnum $status,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            status: $validated['status'] instanceof OfferStatusEnum
                ? $validated['status']
                : OfferStatusEnum::from($validated['status']),
        );
    }
}
