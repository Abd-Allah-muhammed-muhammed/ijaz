<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Http\Resources\General\ReactSelectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Geo\Services\CityService;
use Modules\Geo\Services\NationalityService;
use Modules\Geo\Services\RegionService;
use Modules\Marketplace\Http\Resources\Api\V1\CategoryCollection;
use Modules\Marketplace\Services\CategoryService;
use Modules\Marketplace\Services\SkillService;

class ReactSelectController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly SkillService $skillService,
        private readonly RegionService $regionService,
        private readonly CityService $cityService,
        private readonly NationalityService $nationalityService,
    ) {}

    public function categories(Request $request)
    {
        return $this->successResponse(
            CategoryCollection::make(
                $this->categoryService->listForSelect(
                    $request->search,
                    $request->integer('parent_id'),
                    $request->integer('per_page', 10),
                )
            ),
        );
    }

    public function skills(Request $request): JsonResponse
    {
        $rows = $this->skillService->listForSelect(
            $request->search,
            $request->integer('category_id'),
        );

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function regions(Request $request): JsonResponse
    {
        $rows = $this->regionService->listForSelect($request->search);

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function cities(Request $request): JsonResponse
    {
        $rows = $this->cityService->listForSelect(
            $request->search,
            $request->integer('region_id'),
        );

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function nationalities(Request $request): JsonResponse
    {
        $rows = $this->nationalityService->listForSelect($request->search);

        return $this->successResponse(ReactSelectResource::collection($rows));
    }
}
