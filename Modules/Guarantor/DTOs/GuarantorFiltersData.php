<?php

namespace Modules\Guarantor\DTOs;

use Illuminate\Http\Request;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;

final readonly class GuarantorFiltersData
{
    public function __construct(
        public ?GuarantorStatusEnum $status = null,
        public ?GuarantorTypeEnum $type = null,
        public ?string $role = null,
        public ?string $search = null,
        public ?string $date_from = null,
        public ?string $date_to = null,
        public int $per_page = 10,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            status: $request->enum('status', GuarantorStatusEnum::class),
            type: $request->enum('type', GuarantorTypeEnum::class),
            role: $request->string('role')->value() ?: null,
            search: $request->string('search')->value() ?: null,
            date_from: $request->string('date_from')->value() ?: null,
            date_to: $request->string('date_to')->value() ?: null,
            per_page: $request->integer('per_page', 10),
        );
    }
}
