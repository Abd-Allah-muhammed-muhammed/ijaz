<?php

namespace Modules\Cms\Actions\Page;

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
        return DB::transaction(fn (): Page => $this->repository->update($page, [
            'slug' => Str::slug($dto->slug),
            'translations' => $dto->translations,
        ]));
    }
}
