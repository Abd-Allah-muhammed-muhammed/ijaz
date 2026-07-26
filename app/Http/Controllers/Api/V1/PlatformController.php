<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProviderResource;
use App\Services\Provider\ProviderManagementService;
use App\Support\Phone;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Settings\Services\SettingService;

#[Group('Catalog')]
class PlatformController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ProviderManagementService $providerService,
        private readonly SettingService $settingService,
    ) {}

    /**
     * @unauthenticated
     */
    public function providers(Request $request): JsonResponse
    {
        if (! $request->filled('phone')) {
            return $this->failedMessageResponse(__('phone is required'));
        }

        $phone = Phone::make($request->input('phone'));
        if ($phone->isNotValid()) {
            return $this->failedResponse([
                'phone' => trans('validation.exists', ['attribute' => trans('phone')]),
            ], 'not found');
        }

        $provider = $this->providerService->findByPhone(
            $phone,
            $request->filled('category_id') ? $request->integer('category_id') : null,
        );

        if (! $provider) {
            return $this->failedResponse([
                'phone' => trans('validation.exists', ['attribute' => trans('phone')]),
            ], 'not found');
        }

        return $this->successResponse(ProviderResource::make($provider));
    }

    /**
     * @unauthenticated
     *
     * Public settings dump — allowlisted only.
     *
     * Historically this returned app('settings')->toArray() (every key). That is
     * unsafe once admin-only keys land in the same table. SettingService::publicBag()
     * filters to config('settings.public_keys'), which currently mirrors the full
     * historical seed set (non-breaking). New sensitive keys stay private until
     * deliberately added to that allowlist.
     */
    public function settings(): JsonResponse
    {
        return $this->successResponse($this->settingService->publicBag());
    }
}
