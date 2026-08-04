<?php

namespace Modules\Guarantor\DTOs;

use InvalidArgumentException;
use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;

final readonly class InstallmentData
{
    public function __construct(
        public int $order,
        public float $amount,
        public string $due_date,
    ) {
        if ($this->due_date === '') {
            throw new InvalidArgumentException('Installment due_date is required and must not be empty.');
        }
    }

    /**
     * @param  array{order: int, amount: float|int|string, due_date: string}  $data
     */
    public static function fromArray(array $data): self
    {
        if (! array_key_exists('due_date', $data) || $data['due_date'] === null || $data['due_date'] === '') {
            throw new InvalidArgumentException('Installment due_date is required and must not be empty.');
        }

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
