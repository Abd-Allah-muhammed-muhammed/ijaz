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
        if ($request instanceof StoreCompanyGuarantorRequest) {
            // Company API validates total_amount + project_type only — no title/description/amount.
            // title ← project_type so Dashboard H1, search, and chat operation titles are usable.
            // description stays empty: company has no free-text description field (details live on company_detail).
            $projectType = (string) $request->validated('project_type');

            return new self(
                title: $projectType,
                description: '',
                amount: (float) $request->validated('total_amount'),
                counterparty_phone: (string) $request->validated('counterparty_phone'),
                project_type: $projectType,
            );
        }

        return new self(
            title: (string) $request->validated('title'),
            description: (string) $request->validated('description'),
            amount: (float) $request->validated('amount'),
            counterparty_phone: (string) $request->validated('counterparty_phone'),
            project_type: $request->validated('project_type'),
        );
    }
}
