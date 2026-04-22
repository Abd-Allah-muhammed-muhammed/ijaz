<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PropertyType\PropertyTypeCollection;
use App\QueryFilters\PropertyType\PropertyTypeFilters;
use App\Services\PropertyType\PropertyTypeService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;

#[Group('Property Types')]
class PropertyTypeController extends Controller
{
  use HasApiResponse;

  public function __construct(private readonly PropertyTypeService $propertyTypeService) {}

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
