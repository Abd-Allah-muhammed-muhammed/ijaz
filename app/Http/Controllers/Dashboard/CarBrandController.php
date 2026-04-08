<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CarBrandRequest;
use App\Http\Resources\Dashboard\CarBrandCollection;
use App\Http\Resources\Dashboard\CarBrandResource;
use App\Models\CarBrand;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Throwable;

class CarBrandController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show carBrands', only: ['index', 'show']),
            new Middleware('permission:create carBrands', only: ['create', 'store']),
            new Middleware('permission:edit carBrands', only: ['edit', 'update']),
            new Middleware('permission:delete carBrands', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $carBrands = CarBrand::with(['translation'])
            ->when($request->input('search'), function ($query, $v) {
                return $query->whereTranslationLike('name', "%{$v}%");
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/CarBrands/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => CarBrandCollection::make($carBrands),
        ]);
    }

    public function create()
    {
        return inertia('Dashboard/CarBrands/Create');
    }

    public function store(CarBrandRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('car_brands', 'public');
            }
            CarBrand::create($data);
            DB::commit();

            return redirect()->route('dashboard.car-brands.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function edit(CarBrand $carBrand)
    {
        $carBrand->load(['translations']);

        return inertia('Dashboard/CarBrands/Edit', [
            'carBrand' => CarBrandResource::make($carBrand),
        ]);
    }

    public function update(CarBrandRequest $request, CarBrand $carBrand)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if ($request->hasFile('image')) {
                $carBrand->deleteImage();
                $data['image'] = $request->file('image')->store('car_brands', 'public');
            } else {
                unset($data['image']);
            }
            $carBrand->update($data);
            DB::commit();

            return redirect()->route('dashboard.car-brands.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function updateStatus(Request $request, CarBrand $carBrand)
    {
        DB::beginTransaction();
        try {
            $carBrand->update([
                'is_active' => $request->boolean('is_active'),
            ]);
            DB::commit();

            return redirect()->route('dashboard.car-brands.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function destroy(CarBrand $carBrand)
    {
        $carBrand->deleteImage();
        $carBrand->delete();

        return redirect()->route('dashboard.car-brands.index')->with('success', __('data deleted successfully'));
    }
}
