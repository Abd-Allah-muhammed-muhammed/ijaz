<?php

namespace Modules\Guarantor\Repositories;

use Modules\Guarantor\Contracts\Repositories\CompanyDetailRepositoryInterface;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Modules\Guarantor\Models\GuarantorRequest;

class CompanyDetailRepository implements CompanyDetailRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createForGuarantor(GuarantorRequest $guarantorRequest, array $data): GuarantorCompanyDetail
    {
        return $guarantorRequest->companyDetail()->create($data);
    }
}
