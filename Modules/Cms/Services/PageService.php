<?php

namespace Modules\Cms\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Cms\Actions\Page\DeletePageAction;
use Modules\Cms\Actions\Page\ListPagesAction;
use Modules\Cms\Actions\Page\ListPagesForCatalogAction;
use Modules\Cms\Actions\Page\RenderCmsPageContentAction;
use Modules\Cms\Actions\Page\ShowPageAction;
use Modules\Cms\Actions\Page\ShowPageForCatalogAction;
use Modules\Cms\Actions\Page\StorePageAction;
use Modules\Cms\Actions\Page\UpdatePageAction;
use Modules\Cms\Actions\Page\UploadPageContentImageAction;
use Modules\Cms\Contracts\Repositories\PageRepositoryInterface;
use Modules\Cms\DTOs\StorePageDTO;
use Modules\Cms\DTOs\UpdatePageDTO;
use Modules\Cms\DTOs\UploadedPageContentImageDTO;
use Modules\Cms\DTOs\UploadPageContentImageDTO;
use Modules\Cms\Models\Page;

class PageService
{
    public function __construct(
        private readonly ListPagesAction $listAction,
        private readonly StorePageAction $storeAction,
        private readonly UpdatePageAction $updateAction,
        private readonly DeletePageAction $deleteAction,
        private readonly ShowPageAction $showAction,
        private readonly ListPagesForCatalogAction $listForCatalogAction,
        private readonly ShowPageForCatalogAction $showForCatalogAction,
        private readonly RenderCmsPageContentAction $renderCmsPageContentAction,
        private readonly UploadPageContentImageAction $uploadContentImageAction,
        private readonly PageRepositoryInterface $pageRepository,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StorePageDTO $dto): Page
    {
        return $this->storeAction->handle($dto);
    }

    public function update(Page $page, UpdatePageDTO $dto): Page
    {
        return $this->updateAction->handle($page, $dto);
    }

    public function destroy(Page $page): void
    {
        $this->deleteAction->handle($page);
    }

    public function show(Page $page): Page
    {
        return $this->showAction->handle($page);
    }

    /**
     * @return Collection<int, Page>
     */
    public function listForCatalog(): Collection
    {
        return $this->listForCatalogAction->handle();
    }

    public function showForCatalog(Page $page): Page
    {
        return $this->showForCatalogAction->handle($page);
    }

    public function showForCatalogBySlug(string $slug): Page
    {
        $page = new Page;
        $page->slug = $slug;

        return $this->showForCatalogAction->handle($page);
    }

    /**
     * Final HTML for website + API (card/badge + absolute image URLs, composition-aware).
     */
    public function renderContent(Page $page): string
    {
        return $this->renderCmsPageContentAction->handle($page);
    }

    /**
     * Catalog payload with render-time wrapped `content` (same shape for web + API).
     *
     * @return array{id: int, slug: string, title: string, content: string}
     */
    public function catalogPayload(Page $page): array
    {
        $page = $this->showForCatalog($page);

        return [
            'id' => (int) $page->id,
            'slug' => (string) $page->slug,
            'title' => (string) $page->title,
            'content' => $this->renderContent($page),
        ];
    }

    /**
     * @return array{id: int, slug: string, title: string, content: string}
     */
    public function catalogPayloadBySlug(string $slug): array
    {
        return $this->catalogPayload($this->showForCatalogBySlug($slug));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function compositionOptions(?Page $exclude = null): array
    {
        return $this->pageRepository
            ->getAllForCompositionOptions($exclude?->id)
            ->map(fn (Page $page): array => [
                'value' => (string) $page->slug,
                'label' => (string) ($page->title ?: $page->slug),
            ])
            ->values()
            ->all();
    }

    public function uploadContentImage(UploadPageContentImageDTO $dto): UploadedPageContentImageDTO
    {
        return $this->uploadContentImageAction->handle($dto);
    }
}
