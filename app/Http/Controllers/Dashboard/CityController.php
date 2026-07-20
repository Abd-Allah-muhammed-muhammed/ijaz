<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CityRequest;
use App\Http\Resources\Dashboard\CityCollection;
use App\Http\Resources\Dashboard\CityResource;
use App\Http\Resources\Dashboard\RegionResource;
use App\Services\Normalize\Normalize;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Throwable;

class CityController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show cities', only: ['index', 'show']),
            new Middleware('permission:create cities', only: ['create', 'store']),
            new Middleware('permission:edit cities', only: ['edit', 'update']),
            new Middleware('permission:delete cities', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $row = City::with(['translation', 'region.translation'])
            ->when($request->input('search'), function ($query, $v) {
                $v = Normalize::make($v, app()->getLocale());

                return $query->whereTranslationLike('normalized_title', "%{$v}%");
            })
            ->when($request->input('region_id'), function ($query, $v) {
                return $query->where('region_id', $v);
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/Cities/Index', [
            'prams' => $request->all() ?: [],
            'rows' => CityCollection::make($row),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(CityRequest $request)
    {
        DB::beginTransaction();
        try {
            $city = City::create($request->validated());
            DB::commit();

            return redirect()->route('dashboard.cities.index')->with('success', __('data saved successfully'));
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
        return inertia('Dashboard/Cities/Create', [
            'regions' => RegionResource::collection(Region::with(['translation'])->get()),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(City $city)
    {
        $city->load(['translations']);

        return inertia('Dashboard/Cities/Edit', [
            'row' => CityResource::make($city),
            'regions' => RegionResource::collection(Region::with(['translation'])->get()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     */
    public function update(CityRequest $request, City $city)
    {
        DB::beginTransaction();
        try {
            $city->update($request->validated());
            DB::commit();

            return redirect()->route('dashboard.cities.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws Throwable
     */
    public function destroy(City $city)
    {
        DB::beginTransaction();
        try {
            $city->delete();
            DB::commit();

            return redirect()->route('dashboard.cities.index')->with('success', __('data deleted successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
