<?php

namespace Modules\Classifieds\Actions\PropertyAdvisement;

use Illuminate\Http\Request;
use Modules\Catalog\Models\PropertiyCategory;
use Modules\Catalog\Models\PropertyType;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

class ResolvePropertyAdvisementDashboardSelectsAction
{
    /**
     * @return array{property_type: array{value: int, label: string}|null, city: array{value: int, label: string}|null, region: array{value: int, label: string}|null, category: array{value: int, label: string}|null}
     */
    public function handle(Request $request): array
    {
        $selects = [
            'property_type' => null,
            'city' => null,
            'region' => null,
            'category' => null,
        ];

        if ($type = PropertyType::find($request->property_type_id)) {
            $selects['property_type'] = ['value' => $type->id, 'label' => $type->name];
        }

        if ($city = City::find($request->city_id)) {
            $selects['city'] = ['value' => $city->id, 'label' => $city->title];
        }

        if ($region = Region::find($request->region_id)) {
            $selects['region'] = ['value' => $region->id, 'label' => $region->title];
        }

        if ($category = PropertiyCategory::find($request->category_id)) {
            $selects['category'] = ['value' => $category->id, 'label' => $category->title];
        }

        return $selects;
    }
}
