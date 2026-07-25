<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CityCollection;
use App\Http\Resources\Api\V1\NationalityCollection;
use App\Http\Resources\Api\V1\ProviderResource;
use App\Http\Resources\Api\V1\RegionCollection;
use App\Services\Provider\ProviderManagementService;
use App\Support\Phone;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Geo\Models\Region;
use Modules\Geo\Services\CityService;
use Modules\Geo\Services\NationalityService;
use Modules\Geo\Services\RegionService;

#[Group('Catalog')]
class CatalogController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly RegionService $regionService,
        private readonly CityService $cityService,
        private readonly NationalityService $nationalityService,
        private readonly ProviderManagementService $providerService,
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

    /**
     * @unauthenticated
     */
    public function providers(Request $request): JsonResponse
    {
        if (! $request->filled('phone')) {
            return $this->failedMessageResponse(__('phone is required'));
        }

        $phone = Phone::make($request->input('phone'));
        if ($phone->isNotValid()) {
            return $this->failedResponse([
                'phone' => trans('validation.exists', ['attribute' => trans('phone')]),
            ], 'not found');
        }

        $provider = $this->providerService->findByPhone(
            $phone,
            $request->filled('category_id') ? $request->integer('category_id') : null,
        );

        if (! $provider) {
            return $this->failedResponse([
                'phone' => trans('validation.exists', ['attribute' => trans('phone')]),
            ], 'not found');
        }

        return $this->successResponse(ProviderResource::make($provider));
    }

    /**
     * @unauthenticated
     */
    public function settings(): JsonResponse
    {
        return $this->successResponse(app('settings')->toArray());
    }
}
