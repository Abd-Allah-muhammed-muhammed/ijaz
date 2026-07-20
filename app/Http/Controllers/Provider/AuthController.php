<?php

namespace App\Http\Controllers\Provider;

use App\Enums\ProviderTypeFilesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\Auth\LoginRequest;
use App\Http\Requests\Provider\Auth\UpdateProfileRequest;
use App\Http\Resources\Dashboard\ProviderResource;
use App\Http\Resources\Dashboard\ProviderTypeResource;
use App\Models\CategorySkill;
use App\Models\ProviderCategory;
use App\Models\ProviderType;
use App\Services\Auth\ProviderAuthService;
use App\Services\Sms\Phone;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Geo\Http\Resources\Dashboard\CityResource;
use Modules\Geo\Http\Resources\Dashboard\RegionResource;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Wallet\Http\Resources\Dashboard\WalletTransactionCollection;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly ProviderAuthService $providerAuthService,
    ) {}

    public function loginForm()
    {
        return inertia('Provider/Auth/LoginPage');
    }

    public function login(LoginRequest $request)
    {
        $result = $this->providerAuthService->login($request);

        return redirect()->intended(route($result->redirectRouteName, absolute: false));
    }

    public function logout(Request $request)
    {
        $this->providerAuthService->logout($request);

        return redirect('/');
    }

    public function register(): RedirectResponse
    {
        return to_route('auth.register');
    }

    public function profile()
    {
        $provider = \auth('provider')->user();
        $provider->load([
            'categories' => function ($query) use ($provider) {
                $query->withTranslation()->with([
                    'providerSkills' => function ($q) use ($provider) {
                        $q->withTranslation()
                            ->where('category_skill.provider_id', $provider->id);
                    },
                ]);
            },
            'wallet',
            'providerType',
            'media',
        ]);

        return inertia('Provider/Auth/Profile/Index', [
            'provider' => fn () => ProviderResource::make($provider),
            'types' => fn () => ProviderTypeResource::collection(ProviderType::withTranslation()->get()),
            'regions' => fn () => RegionResource::collection(Region::withTranslation()->get()),
            'cities' => fn () => CityResource::collection(City::withTranslation()->get()),
        ]);
    }

    /**
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $data = $request->validated();
        $provider = \auth('provider')->user();
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
                    $provider->categorySkills
                        ->where('category_id', $providerCategory->category_id)
                        ->delete();

                    return;
                }
                $skills = array_unique($new['skills']);
                if (empty($skills)) {
                    $provider->categorySkills
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

            return to_route('provider.profile')->with('success', __('data updated successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function statements(Request $request)
    {

        $provider = \auth('provider')->user();
        $provider->load([
            'wallet',
        ]);

        return inertia('Provider/Auth/Profile/wallet', [
            'provider' => function () use ($provider) {
                $provider->load([
                    'providerType.translation',
                ]);
                $provider->loadAvg('reviews', 'rating');

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

    public function switchLang(string $locale)
    {
        if (in_array($locale, array_keys(config('laravellocalization.supportedLocales')))) {
            LaravelLocalization::setLocale($locale);
            $user = auth('provider')->user();
            $user->update([
                'language' => $locale,
            ]);

            return redirect()->to(LaravelLocalization::getLocalizedURL($locale, url()->previous()));
        }

        return redirect()->back();
    }
}
