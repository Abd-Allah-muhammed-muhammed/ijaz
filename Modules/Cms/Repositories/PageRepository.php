<?php

namespace Modules\Cms\Repositories;

use App\Support\LookupCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Cms\Contracts\Repositories\PageRepositoryInterface;
use Modules\Cms\Models\Page;

class PageRepository implements PageRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Page::query()
            ->with('translation')
            ->when($request->search, function (Builder $query, mixed $search) {
                return $query->whereTranslationLike('title', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($request->integer('perPage', 10))
            ->withQueryString();
    }

    public function create(array $data): Page
    {
        return Page::query()->create($data);
    }

    public function update(Page $page, array $data): Page
    {
        $page->update($data);

        return $page->fresh(['translations', 'translation']) ?? $page;
    }

    public function delete(Page $page): void
    {
        $page->delete();
    }

    public function loadForEdit(Page $page): Page
    {
        return $page->load(['translations']);
    }

    /**
     * @return Collection<int, Page>
     */
    public function getAllForCatalog(): Collection
    {
        /** @var Collection<int, Page> */
        return LookupCache::rememberForeverForLocale(
            'pages:all',
            app()->getLocale(),
            fn (): Collection => Page::query()
                ->with('translation')
                ->get(),
        );
    }

    public function loadForCatalog(Page $page): Page
    {
        return $this->loadForCatalogBySlug((string) $page->slug);
    }

    public function loadForCatalogBySlug(string $slug): Page
    {
        /** @var Page */
        return LookupCache::rememberForeverScoped(
            'pages:single',
            app()->getLocale(),
            $slug,
            fn (): Page => Page::query()
                ->where('slug', $slug)
                ->with('translation')
                ->firstOrFail(),
        );
    }
}
