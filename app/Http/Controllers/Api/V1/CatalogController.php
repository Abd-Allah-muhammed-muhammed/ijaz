<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Resources\Api\V1\CategoryCollection;
use App\Http\Resources\Api\V1\CityCollection;
use App\Http\Resources\Api\V1\NationalityCollection;
use App\Http\Resources\Api\V1\PageResource;
use App\Http\Resources\Api\V1\ProviderResource;
use App\Http\Resources\Api\V1\QuestionCollection;
use App\Http\Resources\Api\V1\RegionCollection;
use App\Http\Resources\Api\V1\SkillCollection;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Nationality;
use App\Models\Page;
use App\Models\Provider;
use App\Models\Question;
use App\Models\Region;
use App\Models\Skill;
use App\Services\Sms\Phone;
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
          ->with(['children' => function ($query) {
            $query->withTranslation()->limit(6);
          }])
          ->when(
            $request->integer('parent_id'),
            fn($query, $v) => $query->where('parent_id', $v),
            fn($query) => $query->whereNull('parent_id')
          )
          ->when(
            $request->search,
            fn($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
          )
          ->paginate($request->integer('per_page', 10))
      ),
    );
  }

  public function categoriesWithNoChildren(Request $request): JsonResponse
  {
    return $this->successResponse(
      CategoryCollection::make(
        Category::query()
          ->withTranslation()
          ->when(
            $request->integer('parent_id'),
            fn($query, $v) => $query->where('parent_id', $v)
          )
          ->when(
            $request->search,
            fn($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
          )
          ->whereDoesntHave('children')
          ->paginate($request->integer('per_page', 10))
      ),
    );
  }

  public function categoryChildren(Category $category, Request $request): JsonResponse
  {
    return $this->successResponse(
      CategoryCollection::make(
        $category->children()
          ->withTranslation()
          ->when(
            $request->search,
            fn($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
          )
          ->paginate($request->integer('per_page', 10))
      )
    );
  }

  public function categorySkills(Request $request, string $id): JsonResponse
  {

    if ($id == 0) {
      return $this->successResponse(SkillCollection::make(
        Skill::query()
          ->withTranslation()
          ->when(
            $request->search,
            fn($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
          )
          ->paginate($request->integer('per_page', 10))
      ));
    }
    $category = Category::findOrFail($id);

    return $this->successResponse(
      SkillCollection::make(
        $category->skills()
          ->withTranslation()
          ->when(
            $request->search,
            fn($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
          )
          ->paginate($request->integer('per_page', 10))
      )
    );
  }

  public function skills(Request $request): JsonResponse
  {
    return $this->successResponse(SkillCollection::make(
      Skill::query()
        ->when(
          $request->category_id,
          fn($query, $v) => $query->where('category_id', $v)
        )
        ->withTranslation()
        ->when(
          $request->search,
          fn($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
        )
        ->paginate($request->integer('per_page', 10))
    ));
  }

  public function regions(): JsonResponse
  {
    return $this->successResponse(
      RegionCollection::make(
        Region::query()
          ->withTranslation()
          ->when(
            request()->search,
            fn($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
          )
          ->paginate(request()->integer('per_page', 10))
      )
    );
  }

  public function cities(Region $region, Request $request): JsonResponse
  {
    return $this->successResponse(
      CityCollection::make(
        $region->cities()
          ->withTranslation()
          ->when(
            $request->search,
            fn($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
          )
          ->paginate($request->integer('per_page', 10))
      )
    );
  }

  public function nationalities(Request $request): JsonResponse
  {
    return $this->successResponse(
      NationalityCollection::make(
        Nationality::query()
          ->withTranslation()
          ->when(
            $request->search,
            fn($query, $v) => $query->whereTranslationLike('name', "%{$v}%")
          )
          ->paginate($request->integer('per_page', 10))
      )
    );
  }

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
        fn($query, $v) => $query->whereHas('categories', fn($q) => $q->where('categories.id', $v))
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
            fn($query, $v) => $query->whereHas('categories', fn($q) => $q->where('categories.id', $v))
          )
          ->where('phone', $phone)
          ->first()
      )
    );
  }

  public function banners(): JsonResponse
  {
    return $this->successResponse(BannerResource::collection(Banner::all()));
  }

  public function pages(): JsonResponse
  {
    return $this->successResponse(
      Page::with('translation')->get()->map(function (Page $page) {
        return [
          'id' => $page->id,
          'slug' => $page->slug,
          'title' => $page->title,
        ];
      })
    );
  }

  public function page(Page $page): JsonResponse
  {
    $page->load('translation');

    return $this->successResponse(PageResource::make($page));
  }

  public function settings(): JsonResponse
  {
    return $this->successResponse(app('settings')->toArray());
  }

  public function questions(Request $request): JsonResponse
  {
    return $this->successResponse(
      QuestionCollection::make(
        Question::query()
          ->withTranslation()
          ->when(
            $request->search,
            fn($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
          )
          ->paginate(request()->integer('per_page', 10))
      )
    );
  }

  public function counts(): JsonResponse
  {
    // TODO: Implement counts endpoint - currently unused
    return $this->successResponse([]);
  }
}
