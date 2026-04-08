<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;

class AjaxController extends Controller
{
    use HasApiResponse;

    public function category(Category $category): JsonResponse
    {
        $category->load([
            'translation',
            'skills.translation',
        ]);

        return $this->successResponse(
            CategoryResource::make($category)
        );
    }

    public function categories(Request $request): JsonResponse
    {
        return $this->successResponse(
            CategoryResource::collection(
                Category::query()
                    ->withTranslation()
                    ->when(
                        $request->search,
                        fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
                    )
                    ->when(
                        $request->integer('parent_id'),
                        fn ($query, $v) => $query->where('parent_id', $v),
                        fn ($query) => $query->whereNull('parent_id')
                    )
                    ->when($request->provider_type_id && ! $request->parent_id,
                        fn ($query, $v) => $query->whereHas('providerTypes', fn ($q) => $q->where('id', $request->provider_type_id))
                    )
                    ->withExists('children')
                    ->get()
            )
        );
    }
}
