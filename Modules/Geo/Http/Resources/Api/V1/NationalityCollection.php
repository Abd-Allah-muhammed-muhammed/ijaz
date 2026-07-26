<?php

namespace Modules\Geo\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;
// Distinct alias required — see app/Support/HasNormalizedAttributes.php (Pint conflict).
use Modules\Geo\Http\Resources\Api\V1\NationalityResource as ApiNationalityResource;

class NationalityCollection extends BaseCollection
{
    public $collects = ApiNationalityResource::class;
}
