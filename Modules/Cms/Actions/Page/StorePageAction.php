<?php

namespace Modules\Cms\Actions\Page;

use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cms\Contracts\Repositories\PageRepositoryInterface;
use Modules\Cms\DTOs\StorePageDTO;
use Modules\Cms\Models\Page;
use Modules\Cms\Support\PageHtmlSanitizer;
use Throwable;

class StorePageAction
{
    public function __construct(
        private readonly PageRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StorePageDTO $dto): Page
    {
        $page = DB::transaction(fn (): Page => $this->repository->create([
            'slug' => Str::slug($dto->slug),
            'composed_of_slugs' => $dto->composedOfSlugs,
            'translations' => PageHtmlSanitizer::cleanTranslations($dto->translations),
        ]));

        LookupCache::forgetAllLocales('pages:all');
        LookupCache::forgetScopedAllLocales('pages:single', $page->slug);

        return $page;
    }
}
