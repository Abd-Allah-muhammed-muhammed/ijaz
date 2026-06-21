<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OperationStatusEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use Modules\Wallet\Http\Requests\StoreTopUpRequest;
use Modules\Wallet\Http\Requests\StoreWithdrawRequest;
use App\Http\Resources\Api\V1\WalletResource;
use App\Http\Resources\Api\V1\WalletTransactionCollection;
use Modules\Wallet\Http\Resources\TopUpResource;
use Modules\Wallet\Http\Resources\WithdrawRequestResource;
use App\Traits\HasPayments;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Traits\HasWallet;
use DB;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lib\Payment\Facade\Payment;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Throwable;

#[Group('Wallet')]
class WalletController extends Controller
{
    use HasApiResponse;

    public function walletBalance(): JsonResponse
    {
        /**
         * @var HasWallet $user
         */
        $user = auth()->user();

        return $this->successResponse(new WalletResource($user->wallet));
    }

    /**
     * @throws Throwable
     */
    public function walletAddBalance(StoreTopUpRequest $request): JsonResponse
    {
        /**
         * @var HasWallet $user
         */
        $user = auth()->user();
        $data = $request->validated();
        DB::beginTransaction();
        try {
            /**
             * @var TopUpRequest $topRequest
             */
            if ($data['payment_method'] === PaymentMethodEnum::Online->value) {
                $data['payment_status'] = PaymentStatusEnum::Pending;
            }
            $topRequest = $user->topUpRequests()->create([
                ...$data,
                'status' => OperationStatusEnum::Pending,
                'transaction_image' => $request->file('transaction_image')?->store('transactions'),
            ]);

            if ($topRequest->payment_method->isOnline()) {
                $payment = $user->payments()->create([
                    'product_type' => get_class($topRequest),
                    'product_id' => $topRequest->id,
                    'amount' => $topRequest->amount,
                    'status' => PaymentStatusEnum::Pending,
                    'driver' => Payment::getDefaultDriver(),
                ]);
                $paymentResponse = Payment::pay($payment);
                if ($paymentResponse->getStatus() !== 'success') {
                    DB::rollBack();

                    return $this->failedMessageResponse($paymentResponse->getMessage());
                }
                DB::commit();

                return $this->successResponse([
                    ...$paymentResponse->toArray(),
                    'data' => TopUpResource::make($topRequest),
                ]);
            }

            DB::commit();

            return $this->successResponse([
                'status' => 'pending',
                'transaction_id' => '',
                'driver' => 'offline',
                'url' => '',
                'payable' => false,
                'data' => TopUpResource::make($topRequest),
                'message' => trans('top up request created successfully, waiting for admin approval'),
            ]);
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(trans('something went wrong'));
        }
    }

    public function walletWithdraw(StoreWithdrawRequest $request): JsonResponse
    {
        /**
         * @var Model<HasWallet, HasPayments> $user
         */
        $user = auth()->user();

        $data = $request->validated();
        if ($data['amount'] > ($user->wallet->balance - $user->wallet->pending_debit)) {
            return $this->failedMessageResponse(__('You can\'t withdraw this amount.'));
        }
        DB::beginTransaction();
        try {
            $withdrawRequest = $user->withdrawRequests()->create($data);
            $wallet = $user->wallet()->lockForUpdate()->firstOrCreate();

            $user->walletTransactions()->create([
                'wallet_id' => $wallet->id,
                'balance_after' => $wallet->balance,
                'balance_before' => $wallet->balance,
                'operation_type' => get_class($withdrawRequest),
                'operation_id' => $withdrawRequest->id,
                'description' => 'Withdraw Request Created #'.$withdrawRequest->id,
                'pending_debit' => $withdrawRequest->amount,
                'pending_credit' => 0,
            ]);

            $wallet->pending_debit += $withdrawRequest->amount;
            $wallet->save();

            DB::commit();

            return $this->successResponse([
                'status' => 'pending',
                'data' => WithdrawRequestResource::make($withdrawRequest),
                'message' => trans('Withdraw request created successfully and is pending admin approval.'),
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return $this->failedMessageResponse(trans('something went wrong'));
        }
    }

    public function walletTransactions(Request $request): JsonResponse
    {
        /**
         * @var HasWallet $user
         */
        return $this->successResponse(
            WalletTransactionCollection::make(
                auth()->user()->walletTransactions()->latest()
                    ->when($request->data_from, function (Builder $q, $v) {
                        $q->where('created_at', '>=', $v);
                    })
                    ->when($request->data_to, function (Builder $q, $v) {
                        $q->where('created_at', '<=', $v);
                    })
                    ->paginate($request->integer('per_page', 10))
            )
        );
    }
}
