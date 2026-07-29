<?php

namespace Modules\Classifieds\Actions\ElectronicAdvisement;

use Illuminate\Http\Request;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\ElectronicConditionEnum;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

class ResolveElectronicAdvisementDashboardSelectsAction
{
    /**
     * @return array{status: array{value: string, label: string, color: string}|null, condition: array{value: string, label: string, color: string}|null, device_category: array{value: int, label: string}|null, electronic_brand: array{value: int, label: string}|null, city: array{value: int, label: string}|null, region: array{value: int, label: string}|null}
     */
    public function handle(Request $request): array
    {
        $selects = [
            'status' => null,
            'condition' => null,
            'device_category' => null,
            'electronic_brand' => null,
            'city' => null,
            'region' => null,
        ];

        if ($status = AdvisementStatusEnum::tryFrom((string) $request->input('status'))) {
            $selects['status'] = [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ];
        }

        if ($condition = ElectronicConditionEnum::tryFrom((string) $request->input('condition'))) {
            $selects['condition'] = [
                'value' => $condition->value,
                'label' => $condition->label(),
                'color' => $condition->color(),
            ];
        }

        if ($deviceCategory = DeviceCategory::find($request->device_category_id)) {
            $selects['device_category'] = ['value' => $deviceCategory->id, 'label' => $deviceCategory->title];
        }

        if ($electronicBrand = ElectronicBrand::find($request->electronic_brand_id)) {
            $selects['electronic_brand'] = ['value' => $electronicBrand->id, 'label' => $electronicBrand->name];
        }

        if ($city = City::find($request->city_id)) {
            $selects['city'] = ['value' => $city->id, 'label' => $city->title];
        }

        if ($region = Region::find($request->region_id)) {
            $selects['region'] = ['value' => $region->id, 'label' => $region->title];
        }

        return $selects;
    }
}
