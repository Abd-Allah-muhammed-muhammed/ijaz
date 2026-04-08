<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CarTypeRequest;
use App\Http\Resources\Dashboard\CarTypeCollection;
use App\Http\Resources\Dashboard\CarTypeResource;
use App\Models\CarType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Throwable;

class CarTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show carTypes', only: ['index', 'show']),
            new Middleware('permission:create carTypes', only: ['create', 'store']),
            new Middleware('permission:edit carTypes', only: ['edit', 'update']),
            new Middleware('permission:delete carTypes', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $carTypes = CarType::with(['translation', 'carBrand.translation'])
            ->when($request->input('search'), function ($query, $v) {
                return $query->whereTranslationLike('name', "%{$v}%");
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/CarTypes/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => CarTypeCollection::make($carTypes),
        ]);
    }

    public function create()
    {
        return inertia('Dashboard/CarTypes/Create');
    }

    /**
     * @throws Throwable
     */
    public function store(CarTypeRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('car_types', 'public');
            }
            CarType::create($data);
            DB::commit();

            return redirect()->route('dashboard.car-types.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function edit(CarType $carType)
    {
        $carType->load(['translations', 'carBrand']);

        return inertia('Dashboard/CarTypes/Edit', [
            'carType' => CarTypeResource::make($carType),
        ]);
    }

    public function update(CarTypeRequest $request, CarType $carType)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if ($request->hasFile('image')) {
                $carType->deleteImage();
                $data['image'] = $request->file('image')->store('car_types', 'public');
            } else {
                unset($data['image']);
            }
            $carType->update($data);
            DB::commit();

            return redirect()->route('dashboard.car-types.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function updateStatus(Request $request, CarType $carType)
    {
        DB::beginTransaction();
        try {
            $carType->update([
                'is_active' => $request->boolean('is_active'),
            ]);
            DB::commit();

            return redirect()->route('dashboard.car-types.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function destroy(CarType $carType)
    {
        $carType->deleteImage();
        $carType->delete();

        return redirect()->route('dashboard.car-types.index')->with('success', __('data deleted successfully'));
    }
}
