<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Marketplace\Http\Resources\Dashboard\CategoryResource;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Services\CategoryService;

class AjaxController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

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
                $this->categoryService->listForAjax(
                    $request->search,
                    $request->integer('parent_id'),
                    $request->filled('provider_type_id') ? $request->integer('provider_type_id') : null,
                )
            )
        );
    }
}
