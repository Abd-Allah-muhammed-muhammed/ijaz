<?php

namespace Modules\Catalog\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Catalog\Contracts\Services\CarTypeServiceInterface;
use Modules\Catalog\Http\Resources\Api\CarTypeCollection;
use Modules\Catalog\Http\Resources\Api\CarTypeResource;
use Modules\Catalog\Models\CarType;

#[Group('Car Data')]
class CarTypeController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly CarTypeServiceInterface $service,
    ) {}

    /**
     * Get all car types
     *
     * @response CarTypeResource[]
     */
    public function index(Request $request)
    {
        $carTypes = $this->service->index($request);

        return $this->successResponse(CarTypeCollection::make($carTypes));
    }

    /**
     * Get a specific car type
     *
     * @response CarTypeResource
     */
    public function show(CarType $carType)
    {
        $carType = $this->service->show($carType);

        return $this->successResponse(new CarTypeResource($carType));
    }
}
