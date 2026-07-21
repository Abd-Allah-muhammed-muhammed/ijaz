<?php

namespace Modules\Catalog\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Catalog\Contracts\Services\PropertyCategoryServiceInterface;
use Modules\Catalog\Http\Resources\Api\PropertyCategoryCollection;
use Modules\Catalog\QueryFilters\PropertyCategory\PropertyCategoryFilters;

#[Group('Property Categories')]
class PropertyCategoryController extends Controller
{
    use HasApiResponse;

    public function __construct(private readonly PropertyCategoryServiceInterface $propertyCategoryService) {}

    /**
     * Property categories.
     *
     * @unauthenticated
     *
     * @queryParam search string optional Search categories by translated title.
     * @queryParam parent_id integer optional Filter by parent category id. Defaults to top-level categories.
     * @queryParam per_page integer optional Pagination size. Default: 10.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = new PropertyCategoryFilters($request);

        return $this->successResponse(
            PropertyCategoryCollection::make($this->propertyCategoryService->paginate($filters))
        );
    }
}
