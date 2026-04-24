<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\CarCategoryServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CarCategoryResource;
use App\Models\CarCategory;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;

#[Group('Car Data')]
class CarCategoryController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly CarCategoryServiceInterface $service,
    ) {}

    /**
     * Get all car categories
     *
     * @response CarCategoryResource[]
     */
    public function index(Request $request)
    {
        $carCategories = $this->service->index($request);

        return $this->successResponse(CarCategoryResource::collection($carCategories));
    }

    /**
     * Get a specific car category
     *
     * @response CarCategoryResource
     */
    public function show(CarCategory $carCategory)
    {
        $carCategory = $this->service->show($carCategory);

        return $this->successResponse(new CarCategoryResource($carCategory));
    }
}
