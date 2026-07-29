<?php

namespace Modules\Classifieds\Actions\InstituteAdvisement;

use Illuminate\Http\Request;
use Modules\Catalog\Models\Specialization;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\InstituteTypeEnum;
use Modules\Classifieds\Enums\StudyLevelEnum;
use Modules\Classifieds\Enums\StudyTypeEnum;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

class ResolveInstituteAdvisementDashboardSelectsAction
{
    /**
     * @return array{status: array{value: string, label: string, color: string}|null, type: array{value: string, label: string, color: string}|null, study_type: array{value: string, label: string, color: string}|null, study_level: array{value: string, label: string, color: string}|null, specialization: array{value: int, label: string}|null, city: array{value: int, label: string}|null, region: array{value: int, label: string}|null}
     */
    public function handle(Request $request): array
    {
        $selects = [
            'status' => null,
            'type' => null,
            'study_type' => null,
            'study_level' => null,
            'specialization' => null,
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

        if ($type = InstituteTypeEnum::tryFrom((string) $request->input('type'))) {
            $selects['type'] = [
                'value' => $type->value,
                'label' => $type->label(),
                'color' => $type->color(),
            ];
        }

        if ($studyType = StudyTypeEnum::tryFrom((string) $request->input('study_type'))) {
            $selects['study_type'] = [
                'value' => $studyType->value,
                'label' => $studyType->label(),
                'color' => $studyType->color(),
            ];
        }

        if ($studyLevel = StudyLevelEnum::tryFrom((string) $request->input('study_level'))) {
            $selects['study_level'] = [
                'value' => $studyLevel->value,
                'label' => $studyLevel->label(),
                'color' => $studyLevel->color(),
            ];
        }

        if ($specialization = Specialization::find($request->specialization_id)) {
            $selects['specialization'] = ['value' => $specialization->id, 'label' => $specialization->title];
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
