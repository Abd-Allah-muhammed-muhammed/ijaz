<?php

namespace Modules\Catalog\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Catalog\Contracts\Services\CarCategoryServiceInterface;
use Modules\Catalog\Http\Resources\Api\CarCategoryCollection;
use Modules\Catalog\Http\Resources\Api\CarCategoryResource;
use Modules\Catalog\Models\CarCategory;

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

        return $this->successResponse(CarCategoryCollection::make($carCategories));
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
