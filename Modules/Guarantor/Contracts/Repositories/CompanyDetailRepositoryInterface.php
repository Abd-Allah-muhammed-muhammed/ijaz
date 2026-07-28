<?php

namespace Modules\Guarantor\Contracts\Repositories;

use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Modules\Guarantor\Models\GuarantorRequest;

interface CompanyDetailRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createForGuarantor(GuarantorRequest $guarantorRequest, array $data): GuarantorCompanyDetail;
}
