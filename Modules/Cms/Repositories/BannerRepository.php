<?php

namespace Modules\Cms\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Cms\Contracts\Repositories\BannerRepositoryInterface;
use Modules\Cms\Models\Banner;

class BannerRepository implements BannerRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Banner::query()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function findById(int $id): Banner
    {
        return Banner::query()->findOrFail($id);
    }

    public function create(array $data): Banner
    {
        return Banner::query()->create($data);
    }

    public function update(Banner $banner, array $data): Banner
    {
        $banner->update($data);

        return $banner->fresh() ?? $banner;
    }

    public function delete(Banner $banner): void
    {
        $banner->deleteImage();
        $banner->delete();
    }

    /**
     * @return Collection<int, Banner>
     */
    public function all(): Collection
    {
        return Banner::query()->get();
    }
}
