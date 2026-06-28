<?php

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Actions\HandleRajhiCallbackAction;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Models\Payment;
use Throwable;

class RajhiWebhookController extends Controller
{
    public function __construct(
        private readonly HandleRajhiCallbackAction $callbackAction,
    ) {}

    /**
     * Webhook — source of truth for order updates.
     * POST /payments/rajhi/webhook
     *
     * ARB requires response: [{"status":"1"}]
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            // trackId = payment->id (set in InitiateRajhiPaymentAction)
            $trackId = $payload['trackId']
                    ?? $payload['trackid']
                    ?? null;

            if (! $trackId) {
                Log::warning('Rajhi webhook: missing trackId', $payload);

                return response()->json([['status' => '0']]);
            }

            $payment = Payment::find($trackId);

            if (! $payment) {
                Log::warning('Rajhi webhook: payment not found', ['trackId' => $trackId]);

                return response()->json([['status' => '0']]);
            }

            // Idempotency guard
            if ($payment->status !== PaymentStatusEnum::Pending) {
                return response()->json([['status' => '1']]);
            }

            $result = $this->callbackAction->handle($payment, $payload);

            DB::transaction(function () use ($payment, $result) {
                $payment->update([
                    'status' => $result->status,
                    'transaction_id' => $result->transactionId,
                    'response' => $result->rawResponse,
                    'message' => $result->message,
                ]);
            });

            DB::afterCommit(function () use ($payment, $result) {
                $payment->refresh();

                if ($result->isAccepted()) {
                    event(new PaymentCompleted($payment));
                } else {
                    event(new PaymentFailed($payment));
                }
            });

            // ARB requires this exact format
            return response()->json([['status' => '1']]);

        } catch (Throwable $e) {
            Log::error('Rajhi webhook error: '.$e->getMessage(), [
                'payload' => $request->all(),
            ]);

            return response()->json([['status' => '0']]);
        }
    }
}
