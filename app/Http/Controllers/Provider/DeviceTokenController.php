<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\RegisterWebDeviceTokenRequest;
use App\Models\Provider;
use App\Services\Provider\ProviderDeviceTokenService;
use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;

class DeviceTokenController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ProviderDeviceTokenService $providerDeviceTokenService,
    ) {}

    public function store(RegisterWebDeviceTokenRequest $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->user('provider');

        $this->providerDeviceTokenService->registerWebToken(
            $provider,
            $request->string('token')->toString(),
            $request,
        );

        return $this->successMessageResponse(message: 'Device token registered.');
    }
}
