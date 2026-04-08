<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\BannerRequest;
use App\Http\Resources\Dashboard\BannerCollection;
use App\Http\Resources\Dashboard\BannerResource;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Throwable;

class BannerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show banners', only: ['index', 'show']),
            new Middleware('permission:create banners', only: ['create', 'store']),
            new Middleware('permission:edit banners', only: ['edit', 'update']),
            new Middleware('permission:delete banners', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $rows = Banner::paginate($request->integer('per_page', 10))->withQueryString();

        return inertia('Dashboard/Banners/Index', [
            'params' => $request->all() ?: [],
            'rows' => BannerCollection::make($rows),
        ]);
    }

    public function edit(Banner $banner)
    {
        return inertia('Dashboard/Banners/Edit', [
            'row' => BannerResource::make($banner),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function update(BannerRequest $request, Banner $banner)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if ($request->hasFile('image')) {
                $banner->deleteImage();
                $data['image'] = $request->file('image')->store('banners', 'public');
            }
            $banner->update($data);
            DB::commit();

            return redirect()->route('dashboard.banners.index')->with('success', __('data saved successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function store(BannerRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['image'] = $request->file('image')->store('banners', 'public');
            Banner::create($data);
            DB::commit();

            return redirect()->route('dashboard.banners.index')->with('success', __('data saved successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function create()
    {
        return inertia('Dashboard/Banners/Create');
    }

    public function destroy(Banner $banner)
    {
        $banner->deleteImage();
        $banner->delete();

        return redirect()->route('dashboard.banners.index')->with('success', __('data deleted successfully'));
    }
}
