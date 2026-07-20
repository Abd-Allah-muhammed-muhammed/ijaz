<?php

namespace Modules\Classifieds\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Response;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Classifieds\Http\Resources\Dashboard\CarAdvisementCollection;
use Modules\Classifieds\Http\Resources\Dashboard\CarAdvisementResource;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

class CarAdvisementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/CarAdvisement/Index', [
            'rows' => fn () => CarAdvisementCollection::make(
                CarAdvisement::query()
                    ->when($request->search, function ($query, $search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('normalized_title', 'like', "%{$search}%")
                                ->orWhere('normalized_description', 'like', "%{$search}%")
                                ->orWhere('id', 'like', "%{$search}%");
                        });
                    })
                    ->when($request->status, fn ($query, $v) => $query->where('status', $v))
                    ->when($request->operation, fn ($query, $v) => $query->where('operation', $v))
                    ->when($request->usage_status, fn ($query, $v) => $query->where('usage_status', $v))
                    ->when($request->car_brand_id, fn ($query, $v) => $query->where('car_brand_id', $v))
                    ->when($request->car_type_id, fn ($query, $v) => $query->where('car_type_id', $v))
                    ->when($request->car_category_id, fn ($query, $v) => $query->where('car_category_id', $v))
                    ->when($request->city_id, fn ($query, $v) => $query->where('city_id', $v))
                    ->when($request->region_id, fn ($query, $v) => $query->where('region_id', $v))
                    ->paginate($request->integer('per_page', 10))
                    ->withQueryString()
            ),
            'prams' => $request->all() ?: [],
            'selects' => fn () => $this->buildSelectsFromRequest($request),
        ]);
    }

    public function show(CarAdvisement $carAdvisement): Response
    {
        $carAdvisement->load(['carBrand', 'carType', 'carCategory', 'city', 'region', 'user', 'media']);

        return inertia('Dashboard/CarAdvisement/Show', [
            'row' => CarAdvisementResource::make($carAdvisement),
        ]);
    }

    public function update(Request $request, CarAdvisement $carAdvisement): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $carAdvisement->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', __('advisement.status_updated_successfully'));
    }

    /**
     * @return array{car_brand: array{value: int, label: string}|null, car_type: array{value: int, label: string}|null, city: array{value: int, label: string}|null, region: array{value: int, label: string}|null, category: array{value: int, label: string}|null}
     */
    private function buildSelectsFromRequest(Request $request): array
    {
        $selects = [
            'car_brand' => null,
            'car_type' => null,
            'city' => null,
            'region' => null,
            'category' => null,
        ];

        if ($brand = CarBrand::find($request->car_brand_id)) {
            $selects['car_brand'] = ['value' => $brand->id, 'label' => $brand->name];
        }

        if ($type = CarType::find($request->car_type_id)) {
            $selects['car_type'] = ['value' => $type->id, 'label' => $type->name];
        }

        if ($city = City::find($request->city_id)) {
            $selects['city'] = ['value' => $city->id, 'label' => $city->title];
        }

        if ($region = Region::find($request->region_id)) {
            $selects['region'] = ['value' => $region->id, 'label' => $region->title];
        }

        if ($category = CarCategory::find($request->car_category_id)) {
            $selects['category'] = ['value' => $category->id, 'label' => $category->title];
        }

        return $selects;
    }
}
