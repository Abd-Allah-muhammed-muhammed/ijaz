<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Providers\ProviderStatusEnum;
use App\Enums\ProviderTypeFilesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ProviderRequest;
use App\Http\Resources\Dashboard\ProviderCollection;
use App\Http\Resources\Dashboard\ProviderResource;
use App\Http\Resources\Dashboard\ProviderTypeResource;
use App\Models\CategorySkill;
use App\Models\Provider;
use App\Models\ProviderCategory;
use App\Models\ProviderType;
use App\Services\Sms\Phone;
use DB;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rules\Enum;
use Inertia\Response;
use Modules\Geo\Http\Resources\Dashboard\CityResource;
use Modules\Geo\Http\Resources\Dashboard\RegionResource;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Wallet\Http\Resources\Dashboard\WalletTransactionCollection;
use Throwable;

class ProviderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show providers', only: ['index', 'show']),
            new Middleware('permission:create providers', only: ['create', 'store']),
            new Middleware('permission:edit providers', only: ['edit', 'update']),
            new Middleware('permission:delete providers', only: ['destroy']),
            new Middleware('permission:process providers', only: ['updateStatus']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $rows = Provider::with(['providerType', 'wallet', 'latestBlockHistory'])
            ->when($request->input('search'), function ($query, $v) {
                return $query->where(function (Builder $q) use ($v) {
                    $q->where('name', 'like', "%{$v}%")
                        ->orWhere('code', 'like', "%{$v}%");
                });
            })
            ->when($request->input('provider_type_id'), function ($query, $v) {
                return $query->where('provider_type_id', $v);
            })
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/Providers/Index', [
            'prams' => $request->all() ?: [],
            'rows' => ProviderCollection::make($rows),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Provider $provider)
    {
        $provider->load([
            'wallet',
        ]);

        return inertia('Dashboard/Providers/Show', [
            'provider' => function () use ($provider) {
                return ProviderResource::make($provider);
            },
            'transactions' => WalletTransactionCollection::make(
                $provider
                    ->wallet
                    ->transactions()
                    ->latest()
                    ->when($request->input('search'), function ($query, $v) {
                        $query->where(fn (Builder $q) => $q->where('id', 'like', "%{$v}%")->orWhere('operation_id', 'like', "%{$v}%"));
                    })
                    ->paginate($request->integer('per_page', 25))
                    ->withQueryString()
            ),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Provider $provider): Response
    {
        $provider->load([
            'categories' => function ($query) use ($provider) {
                $query->withTranslation()->with([
                    'providerSkills' => function ($q) use ($provider) {
                        $q->withTranslation()
                            ->where('category_skill.provider_id', $provider->id);
                    },
                ]);
            },
            'providerType',
            'media',
        ]);

        return inertia('Dashboard/Providers/Edit', [
            'row' => ProviderResource::make($provider),
            'types' => ProviderTypeResource::collection(ProviderType::withTranslation()->get()),
            'regions' => RegionResource::collection(Region::withTranslation()->get()),
            'cities' => CityResource::collection(City::withTranslation()->get()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     */
    public function update(ProviderRequest $request, Provider $provider): RedirectResponse
    {
        $data = $request->validated();
        $data['phone'] = Phone::make($data['phone'])->toString();
        DB::beginTransaction();
        try {
            if ($request->hasFile('logo')) {
                $data['logo'] = $request->file('logo')->store('providers', 'public');
                $provider->deleteLogo();
            } else {
                unset($data['logo']);
            }
            if ($request->hasFile(ProviderTypeFilesEnum::ID_IMAGE->value)) {
                $provider
                    ->clearMediaCollection(ProviderTypeFilesEnum::ID_IMAGE->value)
                    ->addMediaFromRequest(ProviderTypeFilesEnum::ID_IMAGE->value)
                    ->toMediaCollection(ProviderTypeFilesEnum::ID_IMAGE->value, 'local');
            }
            if ($request->hasFile(ProviderTypeFilesEnum::COMMERCIAL_RECORD->value)) {
                $provider
                    ->clearMediaCollection(ProviderTypeFilesEnum::COMMERCIAL_RECORD->value)
                    ->addMediaFromRequest(ProviderTypeFilesEnum::COMMERCIAL_RECORD->value)
                    ->toMediaCollection(ProviderTypeFilesEnum::COMMERCIAL_RECORD->value, 'local');
            }
            if ($request->hasFile(ProviderTypeFilesEnum::IBAN_CERTIFICATION->value)) {
                $provider
                    ->clearMediaCollection(ProviderTypeFilesEnum::IBAN_CERTIFICATION->value)
                    ->addMediaFromRequest(ProviderTypeFilesEnum::IBAN_CERTIFICATION->value)
                    ->toMediaCollection(ProviderTypeFilesEnum::IBAN_CERTIFICATION->value, 'local');
            }
            if ($request->hasFile(ProviderTypeFilesEnum::FREELANCER_CERTIFICATION->value)) {
                $provider
                    ->clearMediaCollection(ProviderTypeFilesEnum::FREELANCER_CERTIFICATION->value)
                    ->addMediaFromRequest(ProviderTypeFilesEnum::FREELANCER_CERTIFICATION->value)
                    ->toMediaCollection(ProviderTypeFilesEnum::FREELANCER_CERTIFICATION->value, 'local');
            }
            if (! $request->filled('password')) {
                unset($data['password']);
            }

            $provider->update($data);
            $categories = collect($data['categories'])->keyBy('id');
            $old_skills = [];
            $provider->categorySkills->each(function (CategorySkill $item) use (&$old_skills) {
                $old_skills[$item->category_id][] = $item->skill_id;
            });
            $provider->providerCategories->each(function (ProviderCategory $providerCategory) use (&$categories, $old_skills, $provider) {
                $new = $categories->get($providerCategory->category_id);
                if (! $new) {
                    $providerCategory->delete();
                    $provider->categorySkills()
                        ->where('category_id', $providerCategory->category_id)
                        ->delete();

                    return;
                }
                $skills = array_unique($new['skills']);
                if (empty($skills)) {
                    $provider->categorySkills()
                        ->where('category_id', $providerCategory->category_id)
                        ->delete();
                }
                $old_s = $old_skills[$providerCategory->category_id] ?? [];
                $to_delete = array_diff($old_s, $skills);
                if (! empty($to_delete)) {
                    $provider->categorySkills
                        ->where('category_id', $providerCategory->category_id)
                        ->whereIn('skill_id', $to_delete)
                        ->delete();
                }
                $to_add = array_diff($skills, $old_s);
                if (! empty($to_add)) {
                    $provider->categorySkills()->createMany(
                        array_map(static fn ($skill_id) => [
                            'category_id' => $providerCategory->category_id,
                            'skill_id' => $skill_id,
                        ], $to_add
                        ));
                }

                $categories = $categories->forget($providerCategory->category_id);
            });
            if ($categories->isNotEmpty()) {
                foreach ($categories as $cat_id => $item) {
                    $provider->providerCategories()->create([
                        'category_id' => $cat_id,
                    ]);
                    $skills = array_unique($item['skills']);
                    if (! empty($skills)) {
                        $provider->categorySkills()->createMany(
                            array_map(static fn ($skill_id) => ['category_id' => $cat_id, 'skill_id' => $skill_id], $skills)
                        );
                    }
                }
            }

            DB::commit();

            return to_route('dashboard.providers.index')->with('success', __('data updated successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(ProviderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['phone'] = Phone::make($data['phone'])->toString();
        DB::beginTransaction();
        try {
            $data['logo'] = $request->file('logo')?->store('providers', 'public');
            $provider = Provider::create([
                ...$data,
                'status' => ProviderStatusEnum::Pending,
            ]);
            $provider->code = date('dmy').$provider->id;
            $provider->save();
            if ($request->hasFile(ProviderTypeFilesEnum::ID_IMAGE->value)) {
                $provider->addMediaFromRequest(ProviderTypeFilesEnum::ID_IMAGE->value)->toMediaCollection(ProviderTypeFilesEnum::ID_IMAGE->value, 'local');
            }
            if ($request->hasFile(ProviderTypeFilesEnum::COMMERCIAL_RECORD->value)) {
                $provider->addMediaFromRequest(ProviderTypeFilesEnum::COMMERCIAL_RECORD->value)->toMediaCollection(ProviderTypeFilesEnum::COMMERCIAL_RECORD->value, 'local');
            }
            if ($request->hasFile(ProviderTypeFilesEnum::IBAN_CERTIFICATION->value)) {
                $provider->addMediaFromRequest(ProviderTypeFilesEnum::IBAN_CERTIFICATION->value)->toMediaCollection(ProviderTypeFilesEnum::IBAN_CERTIFICATION->value, 'local');
            }
            if ($request->hasFile(ProviderTypeFilesEnum::FREELANCER_CERTIFICATION->value)) {
                $provider->addMediaFromRequest(ProviderTypeFilesEnum::FREELANCER_CERTIFICATION->value)->toMediaCollection(ProviderTypeFilesEnum::FREELANCER_CERTIFICATION->value, 'local');
            }
            $categories = collect($data['categories']);
            $provider->categories()->sync($categories->pluck('id')->toArray());
            $provider->skills()->sync(
                $categories
                    ->map(function ($item) {
                        return array_map(static fn ($skill) => ['category_id' => $item['id'], 'skill_id' => $skill], $item['skills']);
                    })
                    ->flatten(1)
                    ->toArray()
            );
            DB::commit();

            return to_route('dashboard.providers.index')->with('success', __('data saved successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return inertia('Dashboard/Providers/Create', [
            'types' => ProviderTypeResource::collection(ProviderType::withTranslation()->get()),
            'regions' => RegionResource::collection(Region::withTranslation()->get()),
            'cities' => CityResource::collection(City::withTranslation()->get()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws Throwable
     */
    public function destroy(Provider $provider): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $provider->delete();
            DB::commit();

            return redirect()->route('dashboard.providers.index')
                ->with('success', __('data deleted successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }

    }

    public function updateStatus(Request $request, Provider $provider): RedirectResponse
    {
        $request->validate([
            'status' => ['required', new Enum(ProviderStatusEnum::class)],
            'block_days' => 'nullable|integer',
            'block_reason' => 'nullable|string',
        ]);
        $provider->status = $request->status;
        $provider->save();
        if ($request->status == ProviderStatusEnum::Blocked->value) {
            $provider->block($request->block_days ?: 0, $request->block_reason);
        }

        return to_route('dashboard.providers.index')->with('success', __('data saved successfully'));
    }
}
