<?php

namespace Modules\Geo\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Geo\Http\Resources\Api\V1\CityCollection;
use Modules\Geo\Http\Resources\Api\V1\NationalityCollection;
use Modules\Geo\Http\Resources\Api\V1\RegionCollection;
use Modules\Geo\Models\Region;
use Modules\Geo\Services\CityService;
use Modules\Geo\Services\NationalityService;
use Modules\Geo\Services\RegionService;

#[Group('Catalog')]
class GeoController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly RegionService $regionService,
        private readonly CityService $cityService,
        private readonly NationalityService $nationalityService,
    ) {}

    /**
     * @unauthenticated
     */
    public function regions(Request $request): JsonResponse
    {
        return $this->successResponse(
            RegionCollection::make(
                $this->regionService->listForApi(
                    $request->search,
                    $request->integer('per_page', 10),
                )
            )
        );
    }

    /**
     * @unauthenticated
     */
    public function cities(Region $region, Request $request): JsonResponse
    {
        return $this->successResponse(
            CityCollection::make(
                $this->cityService->listForApi(
                    $region,
                    $request->search,
                    $request->integer('per_page', 10),
                )
            )
        );
    }

    /**
     * @unauthenticated
     */
    public function nationalities(Request $request): JsonResponse
    {
        return $this->successResponse(
            NationalityCollection::make(
                $this->nationalityService->listForApi(
                    $request->search,
                    $request->integer('per_page', 10),
                )
            )
        );
    }
}
