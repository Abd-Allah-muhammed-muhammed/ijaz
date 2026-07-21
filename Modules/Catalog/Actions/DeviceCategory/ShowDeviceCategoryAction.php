<?php

namespace Modules\Catalog\Actions\DeviceCategory;

use Modules\Catalog\Models\DeviceCategory;

class ShowDeviceCategoryAction
{
    public function handle(DeviceCategory $deviceCategory): DeviceCategory
    {
        return $deviceCategory
            ->loadCount('children')
            ->load([
                'translation',
                'children.translation',
            ]);
    }
}
