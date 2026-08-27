<?php

namespace Modules\Guarantor\DTOs;

use Modules\Guarantor\Enums\GuarantorStatusEnum;

final readonly class UpdateGuarantorStatusData
{
    public function __construct(
        public GuarantorStatusEnum $status,
        public ?string $reason = null,
        public ?string $notes = null,
    ) {}
}
