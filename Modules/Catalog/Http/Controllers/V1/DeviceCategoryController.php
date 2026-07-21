<?php

namespace Modules\Catalog\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Catalog\Contracts\Services\DeviceCategoryServiceInterface;
use Modules\Catalog\Http\Resources\Api\DeviceCategoryResource;

#[Group('Device Data')]
class DeviceCategoryController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly DeviceCategoryServiceInterface $service,
    ) {}

    /**
     * Get all device categories
     *
     * @response DeviceCategoryResource[]
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            DeviceCategoryResource::collection($this->service->getAll($request))
        );
    }

    /**
     * Get a specific device category
     *
     * @response DeviceCategoryResource
     */
    public function show(int $deviceCategory): JsonResponse
    {
        $record = $this->service->findById($deviceCategory);

        if (! $record) {
            return $this->failedMessageResponse(__('not_found'), 404);
        }

        return $this->successResponse(
            DeviceCategoryResource::make($this->service->show($record))
        );
    }
}
