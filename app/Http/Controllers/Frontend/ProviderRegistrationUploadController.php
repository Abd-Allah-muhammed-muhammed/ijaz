<?php

namespace App\Http\Controllers\Frontend;

use App\DTOs\Auth\StoreProviderRegistrationUploadDTO;
use App\Exceptions\ProviderRegistrationUploadCapExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreProviderRegistrationUploadRequest;
use App\Services\Auth\ProviderRegistrationUploadService;
use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;

class ProviderRegistrationUploadController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ProviderRegistrationUploadService $providerRegistrationUploadService,
    ) {}

    /**
     * @throws ProviderRegistrationUploadCapExceededException
     */
    public function store(StoreProviderRegistrationUploadRequest $request): JsonResponse
    {
        $dto = StoreProviderRegistrationUploadDTO::fromValidated([
            ...$request->validated(),
            'token' => (string) $request->route('token'),
        ]);

        $uploaded = $this->providerRegistrationUploadService->store($dto);

        return $this->successResponse($uploaded->toArray());
    }

    public function destroy(string $token, int $upload): JsonResponse
    {
        $deleted = $this->providerRegistrationUploadService->delete($token, $upload);

        if (! $deleted) {
            return $this->failedMessageResponse(__('provider_registration.upload_not_found'), 404);
        }

        return $this->successResponse([]);
    }
}
