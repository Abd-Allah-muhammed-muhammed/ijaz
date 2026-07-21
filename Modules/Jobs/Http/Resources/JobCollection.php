<?php

namespace Modules\Jobs\Http\Resources;

use App\Http\Resources\Api\BaseCollection;

class JobCollection extends BaseCollection
{
    public $collects = JobResource::class;
}
