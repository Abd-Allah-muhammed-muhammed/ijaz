<?php

namespace Modules\Cms\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Cms\Http\Resources\Api\V1\BannerResource;
use Modules\Cms\Http\Resources\Api\V1\PageResource;
use Modules\Cms\Http\Resources\Api\V1\QuestionCollection;
use Modules\Cms\Models\Page;
use Modules\Cms\Services\BannerService;
use Modules\Cms\Services\PageService;
use Modules\Cms\Services\QuestionService;

#[Group('Catalog')]
class CmsController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly BannerService $bannerService,
        private readonly PageService $pageService,
        private readonly QuestionService $questionService,
    ) {}

    /**
     * @unauthenticated
     */
    public function banners(): JsonResponse
    {
        return $this->successResponse(BannerResource::collection($this->bannerService->all()));
    }

    /**
     * @unauthenticated
     */
    public function pages(): JsonResponse
    {
        return $this->successResponse(
            $this->pageService->listForCatalog()->map(function (Page $page) {
                return [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->title,
                ];
            })
        );
    }

    /**
     * @unauthenticated
     */
    public function page(Page $page): JsonResponse
    {
        return $this->successResponse(
            PageResource::make($this->pageService->showForCatalog($page))
        );
    }

    /**
     * @unauthenticated
     */
    public function questions(Request $request): JsonResponse
    {
        return $this->successResponse(
            QuestionCollection::make($this->questionService->listForApi($request))
        );
    }
}
