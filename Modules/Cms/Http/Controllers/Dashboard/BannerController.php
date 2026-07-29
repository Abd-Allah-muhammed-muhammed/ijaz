<?php

namespace Modules\Cms\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Cms\DTOs\StoreBannerDTO;
use Modules\Cms\DTOs\UpdateBannerDTO;
use Modules\Cms\Http\Requests\Dashboard\BannerRequest;
use Modules\Cms\Http\Resources\Dashboard\BannerCollection;
use Modules\Cms\Http\Resources\Dashboard\BannerResource;
use Modules\Cms\Models\Banner;
use Modules\Cms\Services\BannerService;
use Throwable;

class BannerController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly BannerService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show banners', only: ['index', 'show']),
            new Middleware('permission:create banners', only: ['create', 'store']),
            new Middleware('permission:edit banners', only: ['edit', 'update']),
            new Middleware('permission:delete banners', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $rows = $this->service->index($request);

        return inertia('Dashboard/Banners/Index', [
            'params' => $request->all() ?: [],
            'rows' => BannerCollection::make($rows),
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/Banners/Create');
    }

    public function edit(Banner $banner): Response
    {
        return inertia('Dashboard/Banners/Edit', [
            'row' => BannerResource::make($banner),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(BannerRequest $request): RedirectResponse
    {
        try {
            $this->service->store(StoreBannerDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.banners.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        try {
            $this->service->update($banner, UpdateBannerDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.banners.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->service->destroy($banner);

        return redirect()->route('dashboard.banners.index')->with('success', __('data deleted successfully'));
    }
}
