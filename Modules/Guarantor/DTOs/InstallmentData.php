<?php

namespace Modules\Guarantor\DTOs;

use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;

final readonly class InstallmentData
{
    public function __construct(
        public int $order,
        public float $amount,
        public string $due_date,
    ) {}

    /**
     * @param  array{order: int, amount: float|int|string, due_date: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            order: (int) $data['order'],
            amount: (float) $data['amount'],
            due_date: (string) $data['due_date'],
        );
    }

    /**
     * @return self[]
     */
    public static function collectionFromRequest(
        StoreCompanyGuarantorRequest $request
    ): array {
        /** @var array<array{order: int, amount: float|int|string, due_date: string}> $installments */
        $installments = $request->validated('installments');

        return array_map(
            fn (array $item) => self::fromArray($item),
            $installments
        );
    }
}
