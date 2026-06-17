<?php

namespace Modules\Guarantor\DTOs;

use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;

final readonly class CompanyDetailData
{
    public function __construct(
        public string $company_name,
        public string $commercial_register,
        public ?int $region_id,
        public ?int $city_id,
        public string $authorized_name,
        public string $authorized_id_number,
        public string $authorization_type,
        public string $requester_account_holder,
        public string $requester_iban,
        public string $counterparty_account_holder,
        public ?string $counterparty_iban = null,
    ) {}

    public static function fromRequest(StoreCompanyGuarantorRequest $request): self
    {
        return new self(
            company_name: (string) $request->validated('company_name'),
            commercial_register: (string) $request->validated('commercial_register'),
            region_id: $request->validated('region_id') !== null
                ? (int) $request->validated('region_id')
                : null,
            city_id: $request->validated('city_id') !== null
                ? (int) $request->validated('city_id')
                : null,
            authorized_name: (string) $request->validated('authorized_name'),
            authorized_id_number: (string) $request->validated('authorized_id_number'),
            authorization_type: (string) $request->validated('authorization_type'),
            requester_account_holder: (string) $request->validated('requester_account_holder'),
            requester_iban: (string) $request->validated('requester_iban'),
            counterparty_account_holder: (string) $request->validated('counterparty_account_holder'),
            counterparty_iban: $request->validated('counterparty_iban'),
        );
    }
}
