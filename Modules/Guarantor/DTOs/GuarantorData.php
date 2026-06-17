<?php

namespace Modules\Guarantor\DTOs;

use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;
use Modules\Guarantor\Http\Requests\StoreIndividualGuarantorRequest;

final readonly class GuarantorData
{
    public function __construct(
        public string $title,
        public string $description,
        public float $amount,
        public string $counterparty_phone,
        public ?string $project_type = null,
    ) {}

    public static function fromRequest(
        StoreIndividualGuarantorRequest|StoreCompanyGuarantorRequest $request
    ): self {
        return new self(
            title: (string) $request->validated('title'),
            description: (string) $request->validated('description'),
            amount: (float) $request->validated('amount'),
            counterparty_phone: (string) $request->validated('counterparty_phone'),
            project_type: $request->validated('project_type'),
        );
    }
}
