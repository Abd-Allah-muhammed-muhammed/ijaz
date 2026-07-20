<?php

namespace Modules\Classifieds\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Response;
use Modules\Catalog\Models\PropertiyCategory;
use Modules\Catalog\Models\PropertyType;
use Modules\Classifieds\Http\Resources\Dashboard\PropertyAdvisementCollection;
use Modules\Classifieds\Http\Resources\Dashboard\PropertyAdvisementResource;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

class PropertyAdvisementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/PropertyAdvisement/Index', [
            'rows' => fn () => PropertyAdvisementCollection::make(
                PropertyAdvisement::query()
                    ->when($request->search, function ($query, $search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('normalized_title', 'like', "%{$search}%")
                                ->orWhere('normalized_description', 'like', "%{$search}%")
                                ->orWhere('license', 'like', "%{$search}%")
                                ->orWhere('id', 'like', "%{$search}%");
                        });
                    })
                    ->when($request->status, fn ($query, $v) => $query->where('status', $v))
                    ->when($request->operation, fn ($query, $v) => $query->where('operation', $v))
                    ->when($request->facade, fn ($query, $v) => $query->where('facade', $v))
                    ->when($request->street_width, fn ($query, $v) => $query->where('street_width', $v))
                    ->when($request->street_type, fn ($query, $v) => $query->where('street_type', $v))
                    ->when($request->property_type_id, fn ($query, $v) => $query->where('property_type_id', $v))
                    ->when($request->city_id, fn ($query, $v) => $query->where('city_id', $v))
                    ->when($request->region_id, fn ($query, $v) => $query->where('region_id', $v))
                    ->when($request->category_id, fn ($query, $v) => $query->where('category_id', $v))
                    ->paginate($request->integer('per_page', 10))
                    ->withQueryString()
            ),
            'prams' => $request->all() ?: [],
            'selects' => fn () => $this->buildSelectsFromRequest($request),
        ]);
    }

    public function show(PropertyAdvisement $propertyAdvisement): Response
    {
        $propertyAdvisement->load(['propertyType', 'city', 'region', 'category', 'user', 'media']);

        return inertia('Dashboard/PropertyAdvisement/Show', [
            'row' => PropertyAdvisementResource::make($propertyAdvisement),
        ]);
    }

    public function update(Request $request, PropertyAdvisement $propertyAdvisement): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $propertyAdvisement->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', __('advisement.status_updated_successfully'));
    }

    /**
     * @return array{property_type: array{value: int, label: string}|null, city: array{value: int, label: string}|null, region: array{value: int, label: string}|null, category: array{value: int, label: string}|null}
     */
    private function buildSelectsFromRequest(Request $request): array
    {
        $selects = [
            'property_type' => null,
            'city' => null,
            'region' => null,
            'category' => null,
        ];

        if ($type = PropertyType::find($request->property_type_id)) {
            $selects['property_type'] = ['value' => $type->id, 'label' => $type->name];
        }

        if ($city = City::find($request->city_id)) {
            $selects['city'] = ['value' => $city->id, 'label' => $city->title];
        }

        if ($region = Region::find($request->region_id)) {
            $selects['region'] = ['value' => $region->id, 'label' => $region->title];
        }

        if ($category = PropertiyCategory::find($request->category_id)) {
            $selects['category'] = ['value' => $category->id, 'label' => $category->title];
        }

        return $selects;
    }
}
