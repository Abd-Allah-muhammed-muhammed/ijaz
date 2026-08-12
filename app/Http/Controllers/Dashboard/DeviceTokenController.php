<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\RegisterWebDeviceTokenRequest;
use App\Models\Admin;
use App\Services\Admin\AdminDeviceTokenService;
use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;

class DeviceTokenController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly AdminDeviceTokenService $adminDeviceTokenService,
    ) {}

    public function store(RegisterWebDeviceTokenRequest $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        $this->adminDeviceTokenService->registerWebToken(
            $admin,
            $request->string('token')->toString(),
            $request,
        );

        return $this->successMessageResponse(message: 'Device token registered.');
    }
}
