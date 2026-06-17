<?php

namespace Modules\Guarantor\DTOs;

use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Http\Requests\UpdateGuarantorStatusRequest;

final readonly class UpdateGuarantorStatusData
{
    public function __construct(
        public GuarantorStatusEnum $status,
        public ?string $reason = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateGuarantorStatusRequest $request): self
    {
        return new self(
            status: GuarantorStatusEnum::from((string) $request->validated('status')),
            reason: $request->validated('reason'),
            notes: $request->validated('notes'),
        );
    }
}
