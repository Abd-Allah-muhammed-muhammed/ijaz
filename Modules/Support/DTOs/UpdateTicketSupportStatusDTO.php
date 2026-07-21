<?php

namespace Modules\Support\DTOs;

use Modules\Support\Enums\TicketSupportStatusEnum;

final readonly class UpdateTicketSupportStatusDTO
{
    public function __construct(
        public TicketSupportStatusEnum $status,
    ) {}

    /**
     * @param  array{status: TicketSupportStatusEnum|string}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $status = $validated['status'];

        return new self(
            status: $status instanceof TicketSupportStatusEnum
                ? $status
                : TicketSupportStatusEnum::from($status),
        );
    }
}
