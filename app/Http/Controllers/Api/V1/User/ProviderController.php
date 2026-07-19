<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\findProviderRequest;
use App\Http\Resources\Api\V1\ProviderResource;
use App\Models\Provider;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;

#[Group('Users')]
class ProviderController extends Controller
{
    use HasApiResponse;

    public function get(findProviderRequest $request): JsonResponse
    {
        $provider = Provider::find($request->input('provider_id'));
        if (! $provider) {
            return $this->failedMessageResponse(trans('validation.exists', ['attribute' => trans('provider')]));
        }
        $provider->load(['categories.translation', 'skills.translation', 'reviews.reviewer']);
        $provider->loadAvg('reviews', 'rating');

        return $this->successResponse(ProviderResource::make($provider));
    }
}
