<?php

namespace Modules\Marketplace\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Marketplace\Http\Resources\Api\V1\CategoryCollection;
use Modules\Marketplace\Http\Resources\Api\V1\ProviderTypeCollection;
use Modules\Marketplace\Http\Resources\Api\V1\SkillCollection;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Services\CategoryService;
use Modules\Marketplace\Services\ProviderTypeService;
use Modules\Marketplace\Services\SkillService;

#[Group('Catalog')]
class MarketplaceCatalogController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly SkillService $skillService,
        private readonly ProviderTypeService $providerTypeService,
    ) {}

    /**
     * @unauthenticated
     */
    public function categories(Request $request): JsonResponse
    {
        return $this->successResponse(
            CategoryCollection::make($this->categoryService->listForApi($request)),
        );
    }

    /**
     * @unauthenticated
     */
    public function categoriesWithNoChildren(Request $request): JsonResponse
    {
        return $this->successResponse(
            CategoryCollection::make($this->categoryService->listWithNoChildrenForApi($request)),
        );
    }

    /**
     * @unauthenticated
     */
    public function categoryChildren(Category $category, Request $request): JsonResponse
    {
        return $this->successResponse(
            CategoryCollection::make($this->categoryService->listChildrenForApi($category, $request)),
        );
    }

    /**
     * @unauthenticated
     */
    public function categorySkills(Request $request, string $id): JsonResponse
    {
        if ($id == 0) {
            return $this->successResponse(SkillCollection::make(
                $this->skillService->listForApi($request)
            ));
        }

        return $this->successResponse(
            SkillCollection::make(
                $this->skillService->listForCategoryApi($request, (int) $id)
            )
        );
    }

    /**
     * @unauthenticated
     */
    public function providerTypes(): JsonResponse
    {
        return $this->successResponse(
            ProviderTypeCollection::make($this->providerTypeService->listForApi())
        );
    }
}
