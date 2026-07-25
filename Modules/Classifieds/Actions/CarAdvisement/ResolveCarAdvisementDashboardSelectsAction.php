<?php

namespace Modules\Classifieds\Actions\CarAdvisement;

use Illuminate\Http\Request;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

class ResolveCarAdvisementDashboardSelectsAction
{
    /**
     * @return array{car_brand: array{value: int, label: string}|null, car_type: array{value: int, label: string}|null, city: array{value: int, label: string}|null, region: array{value: int, label: string}|null, category: array{value: int, label: string}|null}
     */
    public function handle(Request $request): array
    {
        $selects = [
            'car_brand' => null,
            'car_type' => null,
            'city' => null,
            'region' => null,
            'category' => null,
        ];

        if ($brand = CarBrand::find($request->car_brand_id)) {
            $selects['car_brand'] = ['value' => $brand->id, 'label' => $brand->name];
        }

        if ($type = CarType::find($request->car_type_id)) {
            $selects['car_type'] = ['value' => $type->id, 'label' => $type->name];
        }

        if ($city = City::find($request->city_id)) {
            $selects['city'] = ['value' => $city->id, 'label' => $city->title];
        }

        if ($region = Region::find($request->region_id)) {
            $selects['region'] = ['value' => $region->id, 'label' => $region->title];
        }

        if ($category = CarCategory::find($request->car_category_id)) {
            $selects['category'] = ['value' => $category->id, 'label' => $category->title];
        }

        return $selects;
    }
}
