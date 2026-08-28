<?php

namespace Modules\Catalog\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Catalog\Contracts\Services\BankServiceInterface;
use Modules\Catalog\Http\Resources\Api\V1\BankCollection;

#[Group('Catalog')]
class BankController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly BankServiceInterface $service,
    ) {}

    /**
     * @unauthenticated
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            BankCollection::make(
                $this->service->listForApi(
                    $request->search,
                    $request->integer('per_page', 10),
                )
            )
        );
    }
}
