<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CityCollection;
use App\Http\Resources\Api\V1\NationalityCollection;
use App\Http\Resources\Api\V1\ProviderResource;
use App\Http\Resources\Api\V1\RegionCollection;
use App\Models\Provider;
use App\Services\Sms\Phone;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Http\Resources\Api\V1\CategoryCollection;
use Modules\Marketplace\Http\Resources\Api\V1\ProviderTypeCollection;
use Modules\Marketplace\Http\Resources\Api\V1\SkillCollection;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Services\CategoryService;
use Modules\Marketplace\Services\ProviderTypeService;
use Modules\Marketplace\Services\SkillService;

#[Group('Catalog')]
class CatalogController extends Controller
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
    public function skills(Request $request): JsonResponse
    {
        return $this->successResponse(SkillCollection::make(
            $this->skillService->listForApi($request)
        ));
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

    /**
     * @unauthenticated
     */
    public function regions(): JsonResponse
    {
        return $this->successResponse(
            RegionCollection::make(
                Region::query()
                    ->withTranslation()
                    ->when(
                        request()->search,
                        fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
                    )
                    ->paginate(request()->integer('per_page', 10))
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
                $region->cities()
                    ->withTranslation()
                    ->when(
                        $request->search,
                        fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
                    )
                    ->paginate($request->integer('per_page', 10))
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
                Nationality::query()
                    ->withTranslation()
                    ->when(
                        $request->search,
                        fn ($query, $v) => $query->whereTranslationLike('name', "%{$v}%")
                    )
                    ->paginate($request->integer('per_page', 10))
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
        $q = Provider::query()
            ->with(['categories.translations'])
            ->when(
                $request->category_id,
                fn ($query, $v) => $query->whereHas('categories', fn ($q) => $q->where('categories.id', $v))
            )
            ->where('phone', $phone)
            ->first();

        if (! $q) {
            return $this->failedResponse([
                'phone' => trans('validation.exists', ['attribute' => trans('phone')]),
            ], 'not found');
        }

        return $this->successResponse(
            ProviderResource::make(
                Provider::query()
                    ->with(['categories.translations'])
                    ->when(
                        $request->category_id,
                        fn ($query, $v) => $query->whereHas('categories', fn ($q) => $q->where('categories.id', $v))
                    )
                    ->where('phone', $phone)
                    ->first()
            )
        );
    }

    /**
     * @unauthenticated
     */
    public function settings(): JsonResponse
    {
        return $this->successResponse(app('settings')->toArray());
    }
}
