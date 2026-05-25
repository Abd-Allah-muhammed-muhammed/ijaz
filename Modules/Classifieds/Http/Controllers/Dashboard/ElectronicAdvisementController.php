<?php

namespace Modules\Classifieds\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\ElectronicConditionEnum;
use Modules\Classifieds\Http\Resources\Dashboard\ElectronicAdvisementCollection;
use Modules\Classifieds\Http\Resources\Dashboard\ElectronicAdvisementResource;
use Modules\Classifieds\Models\ElectronicAdvisement;

class ElectronicAdvisementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show electronicAdvisements', only: ['index', 'show']),
            new Middleware('permission:edit electronicAdvisements', only: ['update']),
            new Middleware('permission:delete electronicAdvisements', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/ElectronicAdvisement/Index', [
            'rows' => fn () => ElectronicAdvisementCollection::make(
                ElectronicAdvisement::query()
                    ->when($request->search, function ($query, $search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('normalized_title', 'like', "%{$search}%")
                                ->orWhere('normalized_description', 'like', "%{$search}%")
                                ->orWhere('id', 'like', "%{$search}%");
                        });
                    })
                    ->when($request->status, fn ($query, $v) => $query->where('status', $v))
                    ->when($request->condition, fn ($query, $v) => $query->where('condition', $v))
                    ->when($request->device_category_id, fn ($query, $v) => $query->where('device_category_id', $v))
                    ->when($request->city_id, fn ($query, $v) => $query->where('city_id', $v))
                    ->when($request->region_id, fn ($query, $v) => $query->where('region_id', $v))
                    ->paginate($request->integer('per_page', 10))
                    ->withQueryString()
            ),
            'prams' => $request->all() ?: [],
            'selects' => fn () => $this->buildSelectsFromRequest($request),
        ]);
    }

    public function show(ElectronicAdvisement $electronicAdvisement): Response
    {
        $electronicAdvisement->load(['deviceCategory', 'city', 'region', 'user', 'media']);

        return inertia('Dashboard/ElectronicAdvisement/Show', [
            'row' => ElectronicAdvisementResource::make($electronicAdvisement),
        ]);
    }

    public function update(Request $request, ElectronicAdvisement $electronicAdvisement): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::enum(AdvisementStatusEnum::class)],
        ]);

        $electronicAdvisement->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', __('advisement.status_updated_successfully'));
    }

    public function destroy(ElectronicAdvisement $electronicAdvisement): RedirectResponse
    {
        $electronicAdvisement->delete();

        return redirect()
            ->route('dashboard.electronic-advisements.index')
            ->with('success', __('data deleted successfully'));
    }

    /**
     * @return array{status: array{value: string, label: string, color: string}|null, condition: array{value: string, label: string, color: string}|null, device_category: array{value: int, label: string}|null, city: array{value: int, label: string}|null, region: array{value: int, label: string}|null}
     */
    private function buildSelectsFromRequest(Request $request): array
    {
        $selects = [
            'status' => null,
            'condition' => null,
            'device_category' => null,
            'city' => null,
            'region' => null,
        ];

        if ($status = AdvisementStatusEnum::tryFrom((string) $request->input('status'))) {
            $selects['status'] = [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ];
        }

        if ($condition = ElectronicConditionEnum::tryFrom((string) $request->input('condition'))) {
            $selects['condition'] = [
                'value' => $condition->value,
                'label' => $condition->label(),
                'color' => $condition->color(),
            ];
        }

        if ($deviceCategory = DeviceCategory::find($request->device_category_id)) {
            $selects['device_category'] = ['value' => $deviceCategory->id, 'label' => $deviceCategory->title];
        }

        if ($city = City::find($request->city_id)) {
            $selects['city'] = ['value' => $city->id, 'label' => $city->title];
        }

        if ($region = Region::find($request->region_id)) {
            $selects['region'] = ['value' => $region->id, 'label' => $region->title];
        }

        return $selects;
    }
}
