<?php

namespace Modules\Classifieds\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Modules\Catalog\Models\Specialization;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\InstituteTypeEnum;
use Modules\Classifieds\Enums\StudyLevelEnum;
use Modules\Classifieds\Enums\StudyTypeEnum;
use Modules\Classifieds\Http\Resources\Dashboard\InstituteAdvisementCollection;
use Modules\Classifieds\Http\Resources\Dashboard\InstituteAdvisementResource;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

class InstituteAdvisementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show instituteAdvisements', only: ['index', 'show']),
            new Middleware('permission:edit instituteAdvisements', only: ['update']),
            new Middleware('permission:delete instituteAdvisements', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/InstituteAdvisement/Index', [
            'rows' => fn () => InstituteAdvisementCollection::make(
                InstituteAdvisement::query()
                    ->when($request->search, function ($query, $search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('normalized_title', 'like', "%{$search}%")
                                ->orWhere('normalized_description', 'like', "%{$search}%")
                                ->orWhere('id', 'like', "%{$search}%");
                        });
                    })
                    ->when($request->status, fn ($query, $v) => $query->where('status', $v))
                    ->when($request->type, fn ($query, $v) => $query->where('type', $v))
                    ->when($request->study_type, fn ($query, $v) => $query->where('study_type', $v))
                    ->when($request->study_level, fn ($query, $v) => $query->where('study_level', $v))
                    ->when($request->specialization_id, fn ($query, $v) => $query->where('specialization_id', $v))
                    ->when($request->city_id, fn ($query, $v) => $query->where('city_id', $v))
                    ->when($request->region_id, fn ($query, $v) => $query->where('region_id', $v))
                    ->paginate($request->integer('per_page', 10))
                    ->withQueryString()
            ),
            'prams' => $request->all() ?: [],
            'selects' => fn () => $this->buildSelectsFromRequest($request),
        ]);
    }

    public function show(InstituteAdvisement $instituteAdvisement): Response
    {
        $instituteAdvisement->load(['specialization', 'city', 'region', 'user', 'media']);

        return inertia('Dashboard/InstituteAdvisement/Show', [
            'row' => InstituteAdvisementResource::make($instituteAdvisement),
        ]);
    }

    public function update(Request $request, InstituteAdvisement $instituteAdvisement): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::enum(AdvisementStatusEnum::class)],
        ]);

        $instituteAdvisement->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', __('advisement.status_updated_successfully'));
    }

    public function destroy(InstituteAdvisement $instituteAdvisement): RedirectResponse
    {
        $instituteAdvisement->delete();

        return redirect()
            ->route('dashboard.institute-advisements.index')
            ->with('success', __('data deleted successfully'));
    }

    /**
     * @return array{status: array{value: string, label: string, color: string}|null, type: array{value: string, label: string, color: string}|null, study_type: array{value: string, label: string, color: string}|null, study_level: array{value: string, label: string, color: string}|null, specialization: array{value: int, label: string}|null, city: array{value: int, label: string}|null, region: array{value: int, label: string}|null}
     */
    private function buildSelectsFromRequest(Request $request): array
    {
        $selects = [
            'status' => null,
            'type' => null,
            'study_type' => null,
            'study_level' => null,
            'specialization' => null,
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

        if ($type = InstituteTypeEnum::tryFrom((string) $request->input('type'))) {
            $selects['type'] = [
                'value' => $type->value,
                'label' => $type->label(),
                'color' => $type->color(),
            ];
        }

        if ($studyType = StudyTypeEnum::tryFrom((string) $request->input('study_type'))) {
            $selects['study_type'] = [
                'value' => $studyType->value,
                'label' => $studyType->label(),
                'color' => $studyType->color(),
            ];
        }

        if ($studyLevel = StudyLevelEnum::tryFrom((string) $request->input('study_level'))) {
            $selects['study_level'] = [
                'value' => $studyLevel->value,
                'label' => $studyLevel->label(),
                'color' => $studyLevel->color(),
            ];
        }

        if ($specialization = Specialization::find($request->specialization_id)) {
            $selects['specialization'] = ['value' => $specialization->id, 'label' => $specialization->title];
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
