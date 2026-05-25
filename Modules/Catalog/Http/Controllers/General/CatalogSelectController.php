<?php

namespace Modules\Catalog\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Http\Resources\General\ReactSelectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\PropertiyCategory;
use Modules\Catalog\Models\PropertyType;
use Modules\Catalog\Models\Specialization;

class CatalogSelectController extends Controller
{
    use HasApiResponse;

    public function propertyTypes(Request $request): JsonResponse
    {
        $rows = PropertyType::query()->withTranslation()
            ->when($request->search, fn ($query, $v) => $query->whereTranslationLike('name', "%{$v}%"))
            ->get();

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function propertyCategories(Request $request): JsonResponse
    {
        $rows = PropertiyCategory::query()->withTranslation()
            ->when($request->search, fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%"))
            ->get();

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function carCategories(Request $request): JsonResponse
    {
        $rows = CarCategory::query()->withTranslation()
            ->when($request->search, fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%"))
            ->get();

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function carTypes(Request $request): JsonResponse
    {
        $rows = CarType::query()->withTranslation()
            ->when($request->search, fn ($query, $v) => $query->whereTranslationLike('name', "%{$v}%"))
            ->when($request->integer('car_brand_id'), fn ($query, $v) => $query->where('car_brand_id', $v))
            ->get();

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function carBrands(Request $request): JsonResponse
    {
        $rows = CarBrand::query()->withTranslation()
            ->when($request->search, fn ($query, $v) => $query->whereTranslationLike('name', "%{$v}%"))
            ->get();

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function deviceCategories(Request $request): JsonResponse
    {
        $rows = DeviceCategory::query()->withTranslation()
            ->when($request->search, fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%"))
            ->get();

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function specializations(Request $request): JsonResponse
    {
        $rows = Specialization::query()->withTranslation()
            ->when($request->search, fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%"))
            ->when($request->integer('parent_id'), fn ($query, $v) => $query->where('parent_id', $v))
            ->get();

        return $this->successResponse(ReactSelectResource::collection($rows));
    }
}
