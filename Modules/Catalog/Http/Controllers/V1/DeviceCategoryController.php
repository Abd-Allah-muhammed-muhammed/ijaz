<?php

namespace Modules\Catalog\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
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
    public function show(DeviceCategory $deviceCategory)
    {
        $deviceCategory = $this->service->show($deviceCategory);

        return $this->successResponse(new DeviceCategoryResource($deviceCategory));
    }
}
