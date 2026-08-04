<?php

namespace Modules\Cms\Actions\Page;

use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cms\Contracts\Repositories\PageRepositoryInterface;
use Modules\Cms\DTOs\UpdatePageDTO;
use Modules\Cms\Models\Page;
use Throwable;

class UpdatePageAction
{
    public function __construct(
        private readonly PageRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Page $page, UpdatePageDTO $dto): Page
    {
        $previousSlug = (string) $page->slug;
        $newSlug = Str::slug($dto->slug);

        $page = DB::transaction(fn (): Page => $this->repository->update($page, [
            'slug' => $newSlug,
            'translations' => $dto->translations,
        ]));

        LookupCache::forgetAllLocales('pages:all');
        LookupCache::forgetScopedAllLocales('pages:single', $previousSlug);
        LookupCache::forgetScopedAllLocales('pages:single', $newSlug);

        return $page;
    }
}
