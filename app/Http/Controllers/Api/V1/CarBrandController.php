<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\CarBrandServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CarBrandResource;
use App\Models\CarBrand;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;

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

        return $this->successResponse(CarBrandResource::collection($carBrands));
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
