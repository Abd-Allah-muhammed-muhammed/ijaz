<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryCollection;
use App\Http\Resources\General\ReactSelectResource;
use App\Models\Category;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;

class ReactSelectController extends Controller
{
    use HasApiResponse;

    public function categories(Request $request)
    {
        return $this->successResponse(
            CategoryCollection::make(
                Category::query()
                    ->withTranslation()
                    ->withExists('children')
                    ->when(
                        $request->integer('parent_id'),
                        fn ($query, $v) => $query->where('parent_id', $v),
                        fn ($query) => $query->whereNull('parent_id')
                    )
                    ->when(
                        $request->search,
                        fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
                    )
                    ->paginate($request->integer('per_page', 10))
            ),
        );
    }

    public function skills(Request $request): JsonResponse
    {
        $rows = Skill::query()->withTranslation()
            ->when($request->search, fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%"))
            ->where('category_id', $request->integer('category_id'))->get();

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function regions(Request $request): JsonResponse
    {
        $rows = Region::query()->withTranslation()
            ->when($request->search, fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%"))
            ->get();

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function cities(Request $request): JsonResponse
    {
        $rows = City::query()->withTranslation()
            ->when($request->search, fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%"))
            ->when($request->integer('region_id'), fn ($query, $v) => $query->where('region_id', $v))
            ->get();

        return $this->successResponse(ReactSelectResource::collection($rows));
    }

    public function nationalities(Request $request): JsonResponse
    {
        $rows = Nationality::query()->withTranslation()
            ->when($request->search, fn ($query, $v) => $query->whereTranslationLike('name', "%{$v}%"))
            ->get();

        return $this->successResponse(ReactSelectResource::collection($rows));
    }
}
