<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GuaranteeRequest\GuaranteeRequestStatusEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Api\User\UpdateGuaranteeRequestStatusRequest;
use App\Http\Requests\Api\V1\GuaranteeRequestRequest;
use App\Http\Resources\Api\V1\GuaranteeRequestCollection;
use App\Http\Resources\Api\V1\GuaranteeRequestResource;
use App\Models\GuaranteeRequest;
use App\Models\Provider;
use App\Models\User;
use App\Services\Sms\Phone;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Lib\Payment\Facade\Payment;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Throwable;

#[Group('Guarantee Requests')]
class GuaranteeRequestController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            GuaranteeRequestCollection::make(
                GuaranteeRequest::where(static function (Builder $q) {
                    $user = auth()->user();
                    $q->whereMorphedTo('user', $user)->orWhereMorphedTo('provider', $user);
                })
                    ->when($request->string('status')->value(), function (Builder $q, $v) {

                        return match ($v) {
                            'pending' => $q->whereIn('status', [
                                GuaranteeRequestStatusEnum::New,
                                GuaranteeRequestStatusEnum::Approved,
                                GuaranteeRequestStatusEnum::InProgress,
                                GuaranteeRequestStatusEnum::EndedByProvider,
                            ]),
                            'completed' => $q->whereIn('status', [
                                GuaranteeRequestStatusEnum::EndedByClient,
                                GuaranteeRequestStatusEnum::Rejected,
                                GuaranteeRequestStatusEnum::Refunded,
                                GuaranteeRequestStatusEnum::CancelledByClient,
                                GuaranteeRequestStatusEnum::CancelledByProvider,
                            ]),
                            default => $q
                        };
                    })
                    ->with(['user', 'provider'])
                    ->latest()
                    ->paginate($request->integer('per_page', 10))
            )
        );
    }

    public function assigned(Request $request): JsonResponse
    {
        return $this->successResponse(
            GuaranteeRequestCollection::make(
                auth()
                    ->user()
                    ->assignedGuaranteeRequests()
                    ->latest()
                    ->paginate($request->integer('per_page', 10))
            )
        );
    }

    /**
     * @throws Throwable
     */
    public function store(GuaranteeRequestRequest $request): JsonResponse
    {
        $data = $request->validated();
        $phone = Phone::make($data['phone']);
        DB::beginTransaction();
        try {
            /**
             * @var class-string<User>|class-string<Provider> $type
             */
            $type = match ($data['provider_type']) {
                'user' => User::class,
                'provider' => Provider::class,
                default => throw new \InvalidArgumentException('Invalid provider type'),
            };
            $guaranteeRequest = auth()->user()->guaranteeRequests()->create([
                ...$data,
                'provider_type' => $type,
                'provider_id' => $type::where('phone', $phone)->value('id'),
            ]);

            if ($request->hasFile('files')) {
                $guaranteeRequest->addMultipleMediaFromRequest(['files'])->each(function ($media) {
                    $media->toMediaCollection();
                });
            }

            $guaranteeRequest->load(['user', 'provider', 'media']);
            DB::commit();

            return $this->successResponse(GuaranteeRequestResource::make($guaranteeRequest));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    public function show(GuaranteeRequest $guaranteeRequest): JsonResponse
    {
        $guaranteeRequest->load(['user', 'provider', 'media']);

        return $this->successResponse(GuaranteeRequestResource::make($guaranteeRequest));
    }

    /**
     * @throws Throwable
     */
    public function edit(GuaranteeRequestRequest $request, GuaranteeRequest $guaranteeRequest): JsonResponse
    {
        $guaranteeRequestUser = $guaranteeRequest->user;
        if ($guaranteeRequestUser->isNot(auth()->user())) {
            return $this->failedMessageResponse(trans('forbidden !!'), ResponseAlias::HTTP_FORBIDDEN);
        }

        $data = $request->validated();
        DB::beginTransaction();
        try {
            /**
             * @var class-string<User>|class-string<Provider> $type
             */
            $type = match ($data['provider_type']) {
                'user' => User::class,
                'provider' => Provider::class,
                default => throw new \InvalidArgumentException('Invalid provider type'),
            };
            $phone = Phone::make($data['phone']);
            $guaranteeRequest->update([
                ...$data,
                'provider_type' => $type,
                'provider_id' => $type::where('phone', $phone)->value('id'),
            ]);

            if ($request->hasFile('files')) {
                $guaranteeRequest->addMultipleMediaFromRequest(['files'])->each(function ($media) {
                    $media->toMediaCollection();
                });
            }

            $guaranteeRequest->load(['user', 'provider', 'media']);
            DB::commit();

            return $this->successResponse(GuaranteeRequestResource::make($guaranteeRequest));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    public function deleteMedia(GuaranteeRequest $guaranteeRequest, Media $media): JsonResponse
    {
        if ($guaranteeRequest->user()->isNot(auth()->user())) {
            return $this->failedMessageResponse(trans('forbidden !!'), Response::HTTP_FORBIDDEN);
        }
        if ($media->model()->isNot($guaranteeRequest)) {
            return $this->failedMessageResponse(trans('forbidden !!'), Response::HTTP_FORBIDDEN);
        }
        if ($guaranteeRequest->status->isNot(GuaranteeRequestStatusEnum::New)) {
            return $this->failedMessageResponse(trans('forbidden !!'), Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $media->delete();
            DB::commit();

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function updateStatus(UpdateGuaranteeRequestStatusRequest $request, GuaranteeRequest $guaranteeRequest): ?JsonResponse
    {
        $data = $request->validated();
        $user = auth()->user();
        if (($guaranteeRequest->user()->isNot($user) && $guaranteeRequest->provider()->isNot($user)) ||
            ! GuaranteeRequestStatusEnum::isAllowed(
                $guaranteeRequest->status,
                GuaranteeRequestStatusEnum::tryFrom($data['status']),
                $guaranteeRequest->user()->is($user) ? 'user' : 'provider'
            )
        ) {
            return $this->failedMessageResponse(__('you can not update this guarantee request status.'));
        }

        DB::beginTransaction();
        try {
            $guaranteeRequest->update(['status' => $data['status']]);
            DB::commit();

            return $this->successMessageResponse(__('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function destroy(GuaranteeRequest $guaranteeRequest): JsonResponse
    {
        $user = auth()->user();
        if (
            $guaranteeRequest->status->isNot(GuaranteeRequestStatusEnum::New) ||
            $guaranteeRequest->user()->isNot($user)
        ) {
            return $this->failedMessageResponse(__('you can not operate on this guarantee request'));
        }
        DB::beginTransaction();
        try {
            $guaranteeRequest->delete();
            DB::commit();

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function pay(GuaranteeRequest $guaranteeRequest): JsonResponse
    {
        $user = auth()->user();

        if (
            $guaranteeRequest->status->isNot(GuaranteeRequestStatusEnum::Approved) ||
            $guaranteeRequest->user()->isNot($user)
        ) {
            return $this->failedMessageResponse(__('you can not pay for this guarantee request'));
        }

        DB::beginTransaction();
        try {
            $payment = $user->payments()->create([
                'amount' => $guaranteeRequest->total,
                'status' => PaymentStatusEnum::Pending,
                'product_type' => GuaranteeRequest::class,
                'product_id' => $guaranteeRequest->id,
                'driver' => Payment::getDefaultDriver(),
            ]);
            DB::commit();
            $paymentObject = Payment::pay($payment);
            if (! $paymentObject->getStatus()) {
                return $this->failedMessageResponse($paymentObject->getMessage());
            }

            return $this->successResponse($paymentObject->toArray());
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
