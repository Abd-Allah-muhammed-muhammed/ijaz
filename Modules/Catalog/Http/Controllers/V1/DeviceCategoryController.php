<?php

namespace Modules\Catalog\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Catalog\Contracts\Services\DeviceCategoryServiceInterface;
use Modules\Catalog\Http\Resources\Api\DeviceCategoryCollection;
use Modules\Catalog\Http\Resources\Api\DeviceCategoryResource;
use Modules\Catalog\Models\DeviceCategory;

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
    public function index(Request $request)
    {
        $deviceCategories = $this->service->index($request);

        return $this->successResponse(DeviceCategoryCollection::make($deviceCategories));
    }

    /**
     * Get a specific device category
     *
     * @response DeviceCategoryResource
     */
    public function show(int $deviceCategory): JsonResponse
    {
        $record = DeviceCategory::find($deviceCategory);

        if (! $record) {
            return $this->failedMessageResponse(__('not_found'), 404);
        }

        return $this->successResponse(
            DeviceCategoryResource::make($this->service->show($record))
        );
    }
}
