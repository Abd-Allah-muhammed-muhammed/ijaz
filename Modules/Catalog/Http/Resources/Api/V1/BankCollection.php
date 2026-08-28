<?php

namespace Modules\Catalog\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;
// Distinct alias required — see app/Support/HasNormalizedAttributes.php (Pint conflict).
use Modules\Catalog\Http\Resources\Api\V1\BankResource as ApiBankResource;

class BankCollection extends BaseCollection
{
    public $collects = ApiBankResource::class;
}
