<?php

namespace Modules\Cms\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Cms\Actions\Banner\DeleteBannerAction;
use Modules\Cms\Actions\Banner\GetAllBannersAction;
use Modules\Cms\Actions\Banner\ListBannersAction;
use Modules\Cms\Actions\Banner\StoreBannerAction;
use Modules\Cms\Actions\Banner\UpdateBannerAction;
use Modules\Cms\DTOs\StoreBannerDTO;
use Modules\Cms\DTOs\UpdateBannerDTO;
use Modules\Cms\Models\Banner;

class BannerService
{
    public function __construct(
        private readonly ListBannersAction $listAction,
        private readonly StoreBannerAction $storeAction,
        private readonly UpdateBannerAction $updateAction,
        private readonly DeleteBannerAction $deleteAction,
        private readonly GetAllBannersAction $allAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StoreBannerDTO $dto): Banner
    {
        return $this->storeAction->handle($dto);
    }

    public function update(Banner $banner, UpdateBannerDTO $dto): Banner
    {
        return $this->updateAction->handle($banner, $dto);
    }

    public function destroy(Banner $banner): void
    {
        $this->deleteAction->handle($banner);
    }

    /**
     * @return Collection<int, Banner>
     */
    public function all(): Collection
    {
        return $this->allAction->handle();
    }
}
