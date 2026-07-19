<?php

namespace Modules\Wallet\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\DTOs\CreateTopUpData;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Exceptions\InsufficientBalanceException;
use Modules\Wallet\Http\Requests\StoreTopUpRequest;
use Modules\Wallet\Http\Requests\StoreWithdrawRequest;
use Modules\Wallet\Http\Resources\TopUpResource;
use Modules\Wallet\Http\Resources\WalletResource;
use Modules\Wallet\Http\Resources\WalletTransactionCollection;
use Modules\Wallet\Http\Resources\WithdrawRequestResource;
use Modules\Wallet\Services\TopUpRequestService;
use Modules\Wallet\Services\WalletService;
use Modules\Wallet\Services\WithdrawRequestService;
use Modules\Wallet\Traits\HasWallet;
use Throwable;

#[Group('Wallet')]
class WalletController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly WalletService $walletService,
        private readonly TopUpRequestService $topUpRequestService,
        private readonly WithdrawRequestService $withdrawRequestService,
        private readonly WalletTransactionRepositoryInterface $transactionRepository,
    ) {}

    public function balance(Request $request): JsonResponse
    {
        /** @var HasWallet $user */
        $user = auth()->user();
        $this->walletService->getBalance($user);

        return $this->successResponse(
            WalletResource::make($user->wallet)
        );
    }

    public function addBalance(StoreTopUpRequest $request): JsonResponse
    {
        $user = auth()->user();

        $imagePath = $request->file('transaction_image')
            ?->store('transactions');

        $data = CreateTopUpData::fromRequest($request->validated(), $imagePath);

        DB::beginTransaction();
        try {
            $result = $this->topUpRequestService->create($user, $data);
            DB::commit();

            $topUpRequest = $result['topUpRequest'];
            $paymentResult = $result['paymentResult'];

            if ($paymentResult !== null) {
                if (! $paymentResult->isSuccessful()) {
                    return $this->failedMessageResponse($paymentResult->message);
                }

                return $this->successResponse([
                    'status' => $paymentResult->status,
                    'driver' => $paymentResult->driver,
                    'url' => $paymentResult->url,
                    'payable' => $paymentResult->payable,
                    'transaction_id' => $paymentResult->transactionId,
                    'message' => $paymentResult->message,
                    'data' => TopUpResource::make($topUpRequest),
                ]);
            }

            return $this->successResponse([
                'status' => 'pending',
                'transaction_id' => '',
                'driver' => 'offline',
                'url' => '',
                'payable' => false,
                'data' => TopUpResource::make($topUpRequest),
                'message' => trans('top up request created successfully, waiting for admin approval'),
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return $this->failedMessageResponse(trans('something went wrong'));
        }
    }

    public function withdraw(StoreWithdrawRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = CreateWithdrawData::fromRequest($request->validated());

        DB::beginTransaction();
        try {
            $withdrawRequest = $this->withdrawRequestService->create($user, $data);
            DB::commit();

            return $this->successResponse([
                'status' => 'pending',
                'data' => WithdrawRequestResource::make($withdrawRequest),
                'message' => trans('Withdraw request created successfully and is pending admin approval.'),
            ]);
        } catch (InsufficientBalanceException $e) {
            DB::rollBack();

            return $this->failedMessageResponse(__("You can't withdraw this amount."));
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

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
