<?php

namespace Modules\Guarantor\DTOs\Dashboard;

use Modules\Guarantor\Enums\GuarantorStatusEnum;

final readonly class AdminUpdateStatusData
{
    public function __construct(
        public GuarantorStatusEnum $status,
        public ?string $reason = null,
        public ?string $notes = null,
    ) {}
}
