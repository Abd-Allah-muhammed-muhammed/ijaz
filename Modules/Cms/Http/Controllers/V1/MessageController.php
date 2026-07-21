<?php

namespace Modules\Cms\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Cms\DTOs\CreateMessageDTO;
use Modules\Cms\Http\Requests\Api\MessagRequest;
use Modules\Cms\Services\MessageService;
use Throwable;

#[Group('Messages')]
class MessageController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly MessageService $service,
    ) {}

    /**
     * @throws Throwable
     */
    public function store(MessagRequest $request): JsonResponse
    {
        try {
            $this->service->create(CreateMessageDTO::fromValidated($request->validated()));

            return $this->successMessageResponse(trans('data saved successfully'));
        } catch (Throwable $e) {
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
