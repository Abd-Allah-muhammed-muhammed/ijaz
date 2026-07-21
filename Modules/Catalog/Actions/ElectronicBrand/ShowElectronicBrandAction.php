<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use Modules\Catalog\Models\ElectronicBrand;

class ShowElectronicBrandAction
{
    public function handle(ElectronicBrand $electronicBrand): ElectronicBrand
    {
        return $electronicBrand->load(['translation']);
    }
}
