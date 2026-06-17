<?php

namespace Modules\Guarantor\Http\Resources\Dashboard;

use App\Http\Resources\Api\BaseCollection;

class GuarantorDashboardCollection extends BaseCollection
{
    public $collects = GuarantorDashboardResource::class;
}
