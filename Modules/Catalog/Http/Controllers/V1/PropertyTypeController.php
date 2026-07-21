<?php

namespace Modules\Catalog\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Catalog\Contracts\Services\PropertyTypeServiceInterface;
use Modules\Catalog\Http\Resources\Api\PropertyTypeCollection;
use Modules\Catalog\QueryFilters\PropertyType\PropertyTypeFilters;

#[Group('Property Types')]
class PropertyTypeController extends Controller
{
    use HasApiResponse;

    public function __construct(private readonly PropertyTypeServiceInterface $propertyTypeService) {}

    /**
     * Property types.
     *
     * @unauthenticated
     *
     * @queryParam search string optional Search property types by translation name.
     * @queryParam per_page integer optional Pagination size. Default: 10.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = new PropertyTypeFilters($request);

        return $this->successResponse(
            PropertyTypeCollection::make($this->propertyTypeService->paginate($filters))
        );
    }
}
