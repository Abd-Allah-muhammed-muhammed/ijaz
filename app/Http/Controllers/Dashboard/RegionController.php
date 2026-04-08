<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\RegionRequest;
use App\Http\Resources\Dashboard\RegionCollection;
use App\Http\Resources\Dashboard\RegionResource;
use App\Models\Region;
use App\Services\Normalize\Normalize;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Throwable;

class RegionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show regions', only: ['index', 'show']),
            new Middleware('permission:create regions', only: ['create', 'store']),
            new Middleware('permission:edit regions', only: ['edit', 'update']),
            new Middleware('permission:delete regions', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $row = Region::with(['translation'])
            ->withCount(['cities'])
            ->when($request->input('search'), function ($query, $v) {
                $v = Normalize::make($v, app()->getLocale());

                return $query->whereTranslationLike('normalized_title', "%{$v}%");
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/Regions/Index', [
            'prams' => $request->all() ?: [],
            'rows' => RegionCollection::make($row),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(RegionRequest $request)
    {
        DB::beginTransaction();
        try {
            $region = Region::create($request->validated());
            DB::commit();

            return redirect()->route('dashboard.regions.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/Regions/Create', []);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Region $region)
    {
        $region->load(['translations']);

        return inertia('Dashboard/Regions/Edit', [
            'row' => RegionResource::make($region),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     */
    public function update(RegionRequest $request, Region $region)
    {
        DB::beginTransaction();
        try {
            $region->update($request->validated());
            DB::commit();

            return redirect()->route('dashboard.regions.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Region $region)
    {
        $region->delete();

        return redirect()->route('dashboard.regions.index')->with('success', __('data deleted successfully'));
    }
}
