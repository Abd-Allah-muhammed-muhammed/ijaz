<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\ProviderRegisterRequest;
use App\Models\Provider;
use App\Rules\ValidPhoneRule;
use App\Services\Auth\ProviderAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Geo\Http\Resources\Dashboard\CityResource;
use Modules\Geo\Http\Resources\Dashboard\RegionResource;
use Modules\Geo\Services\CityService;
use Modules\Geo\Services\RegionService;
use Modules\Marketplace\Http\Resources\Dashboard\ProviderTypeResource;
use Modules\Marketplace\Services\ProviderTypeService;
use Random\RandomException;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly ProviderAuthService $providerAuthService,
        private readonly ProviderTypeService $providerTypeService,
        private readonly RegionService $regionService,
        private readonly CityService $cityService,
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
            'types' => ProviderTypeResource::collection($this->providerTypeService->listForApi()),
            'regions' => RegionResource::collection($this->regionService->listForSelect()),
            'cities' => CityResource::collection($this->cityService->listForSelect()),
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
