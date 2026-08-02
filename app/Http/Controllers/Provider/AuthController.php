<?php

namespace App\Http\Controllers\Provider;

use App\Actions\Locale\SwitchLocaleAction;
use App\DTOs\Provider\UpdateProviderDTO;
use App\Enums\ProviderTypeFilesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\Auth\LoginRequest;
use App\Http\Requests\Provider\Auth\UpdateProfileRequest;
use App\Http\Resources\Dashboard\ProviderResource;
use App\Services\Auth\ProviderAuthService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Geo\Http\Resources\Dashboard\CityResource;
use Modules\Geo\Http\Resources\Dashboard\RegionResource;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Http\Resources\Dashboard\ProviderTypeResource;
use Modules\Marketplace\Models\ProviderType;
use Modules\Wallet\Http\Resources\Dashboard\WalletTransactionCollection;
use Modules\Wallet\Support\WalletSearch;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly ProviderAuthService $providerAuthService,
        private readonly SwitchLocaleAction $switchLocaleAction,
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
        $provider = \auth('provider')->user();
        $dto = UpdateProviderDTO::fromValidated(
            $request->validated(),
            $request->file('logo'),
            $this->collectTypeFiles($request),
        );

        try {
            $this->providerAuthService->updateProfile($provider, $dto);

            return to_route('provider.profile')->with('success', __('data updated successfully'));
        } catch (Throwable $e) {
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
                    ->when(WalletSearch::normalize($request->input('search')), function ($query, string $search): void {
                        $query->where(function (Builder $q) use ($search): void {
                            $q->where('id', 'like', "%{$search}%")
                                ->orWhere('operation_id', 'like', "%{$search}%")
                                ->orWhere('payment_id', 'like', "%{$search}%");
                        });
                    })
                    ->paginate($request->integer('per_page', 25))
                    ->withQueryString()
            ),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    public function switchLang(string $locale): RedirectResponse
    {
        $url = $this->switchLocaleAction->handle($locale);

        if ($url === null) {
            return redirect()->back();
        }

        auth('provider')->user()->update([
            'language' => $locale,
        ]);

        return redirect()->to($url);
    }

    /**
     * @return array<string, UploadedFile>
     */
    private function collectTypeFiles(UpdateProfileRequest $request): array
    {
        $files = [];

        foreach (ProviderTypeFilesEnum::cases() as $file) {
            if ($request->hasFile($file->value)) {
                $files[$file->value] = $request->file($file->value);
            }
        }

        return $files;
    }
}
