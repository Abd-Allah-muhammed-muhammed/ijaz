<?php

namespace Modules\Catalog\Actions\CarType;

use Modules\Catalog\Models\CarType;

class ShowCarTypeAction
{
    public function handle(CarType $carType): CarType
    {
        return $carType->load(['translation', 'carBrand.translation']);
    }
}
