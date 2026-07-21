<?php

namespace Modules\Catalog\Actions\CarCategory;

use Modules\Catalog\Models\CarCategory;

class ShowCarCategoryAction
{
    public function handle(CarCategory $carCategory): CarCategory
    {
        return $carCategory->load(['translation']);
    }
}
