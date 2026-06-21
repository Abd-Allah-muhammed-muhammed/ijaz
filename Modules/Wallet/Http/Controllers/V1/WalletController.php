<?php

namespace Modules\Wallet\Http\Controllers\V1;

use App\Enums\OperationStatusEnum;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Payment\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Traits\HasPayments;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Lib\Payment\Facade\Payment;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\Http\Requests\StoreTopUpRequest;
use Modules\Wallet\Http\Requests\StoreWithdrawRequest;
use Modules\Wallet\Http\Resources\TopUpResource;
use Modules\Wallet\Http\Resources\WalletResource;
use Modules\Wallet\Http\Resources\WalletTransactionCollection;
use Modules\Wallet\Http\Resources\WithdrawRequestResource;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Services\WalletService;
use Modules\Wallet\Traits\HasWallet;
use Throwable;

#[Group('Wallet')]
class WalletController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly WalletService $walletService,
        private readonly WalletTransactionRepositoryInterface $transactionRepository,
    ) {}

    public function balance(Request $request): JsonResponse
    {
        /** @var HasWallet $user */
        $user = auth()->user();

        return $this->successResponse(
            WalletResource::make($user->wallet)
        );
    }

    /**
     * @throws Throwable
     */
    public function addBalance(StoreTopUpRequest $request): JsonResponse
    {
        /** @var HasWallet&HasPayments $user */
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();
        try {
            if ($data['payment_method'] === PaymentMethodEnum::Online->value) {
                $data['payment_status'] = PaymentStatusEnum::Pending;
            }

            /** @var TopUpRequest $topRequest */
            $topRequest = $user->topUpRequests()->create([
                ...$data,
                'status' => OperationStatusEnum::Pending,
                'wallet_id' => $user->wallet->id,
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

    public function withdraw(StoreWithdrawRequest $request): JsonResponse
    {
        /** @var HasWallet $user */
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();
        try {
            if (! $this->walletService->canWithdraw($user, (float) $data['amount'])) {
                DB::rollBack();

                return $this->failedMessageResponse(__('You can\'t withdraw this amount.'));
            }

            $withdrawRequest = $user->withdrawRequests()->create($data);

            $this->walletService->addPendingDebit(
                $user,
                (float) $data['amount'],
                $withdrawRequest,
                'Withdraw Request Created #'.$withdrawRequest->id,
            );

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

    public function transactions(Request $request): JsonResponse
    {
        /** @var HasWallet $user */
        $user = auth()->user();

        return $this->successResponse(
            WalletTransactionCollection::make(
                $this->transactionRepository->listForOwner(
                    $user,
                    $request->integer('per_page', 10),
                    $request->input('data_from'),
                    $request->input('data_to'),
                )
            )
        );
    }
}
