<?php

namespace Modules\Catalog\Actions\CarBrand;

use Modules\Catalog\Models\CarBrand;

class ShowCarBrandAction
{
    public function handle(CarBrand $carBrand): CarBrand
    {
        return $carBrand->load(['translation']);
    }
}
