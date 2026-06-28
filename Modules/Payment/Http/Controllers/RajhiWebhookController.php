<?php

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Actions\HandleRajhiWebhookAction;
use Throwable;

class RajhiWebhookController extends Controller
{
    public function __construct(
        private readonly HandleRajhiWebhookAction $webhookAction,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            $this->webhookAction->handle($request->all());

            return response()->json([['status' => '1']]);
        } catch (Throwable $e) {
            Log::error('Rajhi webhook error: '.$e->getMessage(), [
                'payload' => $request->all(),
            ]);

            return response()->json([['status' => '0']]);
        }
    }
}
