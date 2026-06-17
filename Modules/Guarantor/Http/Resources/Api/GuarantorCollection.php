<?php

namespace Modules\Guarantor\Http\Resources\Api;

use App\Http\Resources\Api\BaseCollection;

class GuarantorCollection extends BaseCollection
{
    public $collects = GuarantorResource::class;
}
