<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\FindProviderRequest;
use App\Http\Resources\Api\V1\ProviderResource;
use App\Services\Provider\ProviderManagementService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;

#[Group('Users')]
class ProviderController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ProviderManagementService $providerService,
    ) {}

    public function get(FindProviderRequest $request): JsonResponse
    {
        $provider = $this->providerService->findForApi($request->input('provider_id'));

        if (! $provider) {
            return $this->failedMessageResponse(trans('validation.exists', ['attribute' => trans('provider')]));
        }

        return $this->successResponse(ProviderResource::make($provider));
    }
}
