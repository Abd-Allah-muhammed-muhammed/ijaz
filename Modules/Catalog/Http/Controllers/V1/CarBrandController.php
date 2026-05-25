<?php

namespace Modules\Catalog\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Catalog\Contracts\Services\CarBrandServiceInterface;
use Modules\Catalog\Http\Resources\Api\CarBrandCollection;
use Modules\Catalog\Http\Resources\Api\CarBrandResource;
use Modules\Catalog\Models\CarBrand;

#[Group('Car Data')]
class CarBrandController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly CarBrandServiceInterface $service,
    ) {}

    /**
     * Get all car brands
     *
     * @response CarBrandResource[]
     */
    public function index(Request $request)
    {
        $carBrands = $this->service->index($request);

        return $this->successResponse(CarBrandCollection::make($carBrands));
    }

    /**
     * Get a specific car brand
     *
     * @response CarBrandResource
     */
    public function show(CarBrand $carBrand)
    {
        $carBrand = $this->service->show($carBrand);

        return $this->successResponse(new CarBrandResource($carBrand));
    }
}
