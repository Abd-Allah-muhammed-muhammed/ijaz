<?php

namespace Modules\Catalog\Http\Resources\Dashboard;

use App\Http\Resources\Api\BaseCollection;
// Distinct alias required — see app/Support/HasNormalizedAttributes.php (Pint conflict).
use Modules\Catalog\Http\Resources\Dashboard\BankResource as DashboardBankResource;

class BankCollection extends BaseCollection
{
    public $collects = DashboardBankResource::class;
}
