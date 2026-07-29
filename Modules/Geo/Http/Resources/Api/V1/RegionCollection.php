<?php

namespace Modules\Geo\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;
// Distinct alias required — see app/Support/HasNormalizedAttributes.php (Pint conflict).
use Modules\Geo\Http\Resources\Api\V1\RegionResource as ApiRegionResource;

class RegionCollection extends BaseCollection
{
    public $collects = ApiRegionResource::class;
}
