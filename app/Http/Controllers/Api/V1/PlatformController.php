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

#[Group('Catalog')]
class PlatformController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ProviderManagementService $providerService,
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
     */
    public function settings(): JsonResponse
    {
        return $this->successResponse(app('settings')->toArray());
    }
}
