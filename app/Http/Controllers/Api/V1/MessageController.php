<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MessagRequest;
use App\Models\Message;
use DB;
use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Throwable;

class MessageController extends Controller
{
    use HasApiResponse;

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(MessagRequest $request): JsonResponse
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            Message::create($validated);
            DB::commit();

            return $this->successMessageResponse(trans('data saved successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
