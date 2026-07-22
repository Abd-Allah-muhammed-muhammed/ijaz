<?php

namespace Modules\Orders\DTOs;

final readonly class StoreOrderOfferDTO
{
    public function __construct(
        public float $price,
        public string $description,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            price: (float) $validated['price'],
            description: (string) $validated['description'],
        );
    }
}
