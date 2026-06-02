<?php

namespace Modules\Catalog\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Catalog\Contracts\Services\SpecializationServiceInterface;
use Modules\Catalog\Http\Resources\Api\SpecializationCollection;
use Modules\Catalog\Http\Resources\Api\SpecializationResource;
use Modules\Catalog\Models\Specialization;

#[Group('Specialization Data')]
class SpecializationController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly SpecializationServiceInterface $service,
    ) {}

    /**
     * Get all specializations
     *
     * @response SpecializationResource[]
     */
    public function index(Request $request)
    {
        $specializations = $this->service->index($request);

        return $this->successResponse(SpecializationCollection::make($specializations));
    }

    /**
     * Get a specific specialization
     *
     * @response SpecializationResource
     */
    public function show(int $specialization): JsonResponse
    {
        $record = Specialization::find($specialization);

        if (! $record) {
            return $this->failedMessageResponse(__('not_found'), 404);
        }

        return $this->successResponse(
            SpecializationResource::make($this->service->show($record))
        );
    }
}
