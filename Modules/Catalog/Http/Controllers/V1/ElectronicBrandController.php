<?php

namespace Modules\Catalog\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Catalog\Contracts\Services\ElectronicBrandServiceInterface;
use Modules\Catalog\Http\Resources\Api\ElectronicBrandCollection;
use Modules\Catalog\Http\Resources\Api\ElectronicBrandResource;
use Modules\Catalog\Models\ElectronicBrand;

#[Group('Electronic Data')]
class ElectronicBrandController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ElectronicBrandServiceInterface $service,
    ) {}

    /**
     * Get all active electronic brands
     *
     * @response ElectronicBrandResource[]
     */
    public function index(Request $request)
    {
        $request->merge(['is_active' => true]);

        $electronicBrands = $this->service->index($request);

        return $this->successResponse(ElectronicBrandCollection::make($electronicBrands));
    }

    /**
     * Get a specific electronic brand
     *
     * @response ElectronicBrandResource
     */
    public function show(int $electronicBrand): JsonResponse
    {
        $record = ElectronicBrand::find($electronicBrand);

        if (! $record) {
            return $this->failedMessageResponse(__('not_found'), 404);
        }

        return $this->successResponse(
            ElectronicBrandResource::make($this->service->show($record))
        );
    }
}
