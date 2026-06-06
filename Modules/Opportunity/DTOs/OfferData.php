<?php

namespace Modules\Opportunity\DTOs;

use Modules\Opportunity\Http\Requests\StoreOfferRequest;

final readonly class OfferData
{
    public function __construct(
        public float $price,
        public ?string $description,
    ) {}

    public static function fromRequest(StoreOfferRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            price: (float) $validated['price'],
            description: $validated['description'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistenceArray(): array
    {
        return [
            'price' => $this->price,
            'description' => $this->description,
        ];
    }
}
