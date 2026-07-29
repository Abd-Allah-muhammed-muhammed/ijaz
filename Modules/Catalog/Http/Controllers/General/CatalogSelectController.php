<?php

namespace Modules\Catalog\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Http\Resources\General\ReactSelectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Catalog\Contracts\Services\CarBrandServiceInterface;
use Modules\Catalog\Contracts\Services\CarCategoryServiceInterface;
use Modules\Catalog\Contracts\Services\CarTypeServiceInterface;
use Modules\Catalog\Contracts\Services\DeviceCategoryServiceInterface;
use Modules\Catalog\Contracts\Services\ElectronicBrandServiceInterface;
use Modules\Catalog\Contracts\Services\PropertyCategoryServiceInterface;
use Modules\Catalog\Contracts\Services\PropertyTypeServiceInterface;
use Modules\Catalog\Contracts\Services\SpecializationServiceInterface;

class CatalogSelectController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly PropertyTypeServiceInterface $propertyTypeService,
        private readonly PropertyCategoryServiceInterface $propertyCategoryService,
        private readonly CarCategoryServiceInterface $carCategoryService,
        private readonly CarTypeServiceInterface $carTypeService,
        private readonly CarBrandServiceInterface $carBrandService,
        private readonly DeviceCategoryServiceInterface $deviceCategoryService,
        private readonly ElectronicBrandServiceInterface $electronicBrandService,
        private readonly SpecializationServiceInterface $specializationService,
    ) {}

    public function propertyTypes(Request $request): JsonResponse
    {
        $rows = $this->propertyTypeService->listForSelect($request->search);

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function propertyCategories(Request $request): JsonResponse
    {
        $rows = $this->propertyCategoryService->listForSelect($request->search);

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function carCategories(Request $request): JsonResponse
    {
        $rows = $this->carCategoryService->listForSelect($request->search);

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function carTypes(Request $request): JsonResponse
    {
        $rows = $this->carTypeService->listForSelect(
            $request->search,
            $request->integer('car_brand_id'),
        );

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function carBrands(Request $request): JsonResponse
    {
        $rows = $this->carBrandService->listForSelect($request->search);

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function deviceCategories(Request $request): JsonResponse
    {
        $rows = $this->deviceCategoryService->listForSelect($request->search);

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function electronicBrands(Request $request): JsonResponse
    {
        $rows = $this->electronicBrandService->listForSelect($request->search);

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function specializations(Request $request): JsonResponse
    {
        $rows = $this->specializationService->listForSelect(
            $request->search,
            $request->integer('parent_id'),
        );

        return $this->successResponse(ReactSelectResource::collection($rows));
    }
}
