<?php

namespace Modules\Catalog\Actions\DeviceCategory;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\DeviceCategory;

class ListDeviceCategoriesForSelectAction
{
    /**
     * @return Collection<int, DeviceCategory>
     */
    public function handle(?string $search = null): Collection
    {
        return DeviceCategory::query()->withTranslation()
            ->when($search, fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%"))
            ->get();
    }
}
