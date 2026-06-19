<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryCollection;
use App\Http\Resources\Api\V1\SkillCollection;
use App\Models\Category;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;

class CatalogController extends Controller
{
    use HasApiResponse;

    public function categories(Request $request): JsonResponse
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
        return $this->successResponse(SkillCollection::make(
            Skill::query()
                ->when(
                    $request->category_id,
                    fn ($query, $v) => $query->where('category_id', $v)
                )
                ->withTranslation()
                ->when(
                    $request->search,
                    fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
                )
                ->paginate($request->integer('per_page', 10))
        ));
    }

    public function banners()
    {
        // Assuming you have a Banner model and a corresponding resource
        return $this->successResponse([]);
    }
}
