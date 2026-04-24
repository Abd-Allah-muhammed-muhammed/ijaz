<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\CarTypeServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CarTypeResource;
use App\Models\CarType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;

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

        return $this->successResponse(CarTypeResource::collection($carTypes));
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
