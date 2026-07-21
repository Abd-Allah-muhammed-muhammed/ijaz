<?php

namespace Modules\Cms\Actions\Page;

use Modules\Cms\Contracts\Repositories\PageRepositoryInterface;
use Modules\Cms\Models\Page;

class DeletePageAction
{
    public function __construct(
        private readonly PageRepositoryInterface $repository,
    ) {}

    public function handle(Page $page): void
    {
        $this->repository->delete($page);
    }
}
