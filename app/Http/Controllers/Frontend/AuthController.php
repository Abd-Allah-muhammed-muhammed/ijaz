<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderRegisterRequest;
use App\Http\Resources\Dashboard\CityResource;
use App\Http\Resources\Dashboard\ProviderTypeResource;
use App\Http\Resources\Dashboard\RegionResource;
use App\Models\Provider;
use App\Models\ProviderType;
use App\Rules\ValidPhoneRule;
use App\Services\Auth\ProviderAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Random\RandomException;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly ProviderAuthService $providerAuthService,
    ) {}

    /**
     * @throws Throwable
     */
    public function store(ProviderRegisterRequest $request): RedirectResponse
    {
        try {
            $result = $this->providerAuthService->register($request->validated(), $request);
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }

        if (! $result->success) {
            return redirect()->back()->with('error', $result->errorMessage);
        }

        return to_route('auth.register')
            ->with('success', __('data saved successfully'))
            ->with('id', $result->provider->id);
    }

    public function create()
    {
        return inertia('Frontend/Auth/Register_', [
            'types' => ProviderTypeResource::collection(ProviderType::withTranslation()->get()),
            'regions' => RegionResource::collection(Region::withTranslation()->get()),
            //      'regions' => [],
            'cities' => CityResource::collection(City::withTranslation()->get()),
            //      'cities' => [],
            //      'categories' => CategoryResource::collection(
            //        Category::whereDoesntHave('parent')
            //          ->with('translation')
            //          ->with('childrenRecursive.translation')
            //          ->get()
            //      )
        ]);
    }

    /**
     * @throws RandomException
     */
    public function otp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'numeric', 'digits_between:8,15', new ValidPhoneRule(new Provider)],
        ], [], [
            'phone' => __('phone'),
        ]);

        $this->providerAuthService->sendRegistrationOtp($request->phone);

        return response()->json([]);
    }
}
