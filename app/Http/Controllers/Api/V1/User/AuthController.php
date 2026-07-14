<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Enums\Users\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\LoginRequest;
use App\Http\Requests\Api\V1\User\RegisterRequest;
use App\Http\Requests\Api\V1\User\UpdateRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Services\Sms\Phone;
use App\Traits\OTPGeneration;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\Services\SmsService;
use Random\RandomException;
use Throwable;

#[Group('Auth')]
class AuthController extends Controller
{
    use HasApiResponse, OTPGeneration;

    public function __construct(
        private readonly SmsService $smsService,
    ) {}

    /**
     * Authenticate a user and return a short-lived login token.
     *
     * @unauthenticated
     *
     * @throws RandomException
     */
    public function login(LoginRequest $request): JsonResponse
    {

        $phone = Phone::make($request->phone);
        $user = User::where('phone', $phone)->first();
        if (! $user) {
            return $this->failedMessageResponse(trans('user not found'), 400);
        }
        if ($user->status->isNot(UserStatusEnum::Active)) {
            $msg = match ($user->status) {
                UserStatusEnum::Deleted => trans('this account is deleted'),
                UserStatusEnum::Blocked => $user->blocked_until ? trans('this account is blocked') : trans('this account is banned'),
                default => trans('this account is not active '),
            };

            return $this->failedMessageResponse($msg, 400);
        }
        $code = $user->updateOrCreateVerificationCode($this->generateOtpForPhone($phone), 'login');
        $result = $this->smsService->send(
            SmsMessage::otp($code->token),
            Phone::make($user->phone)->toString()
        );
        Log::channel('sms')->info('Login OTP for user '.$user->id.' is '.$code->token, $result->toArray());
        $user->tokens()->delete(); // Delete previous login token

        return $this->successResponseWithToken(
            [],
            explode('|', $user->createToken('login', [], now()->addMinutes(15))->plainTextToken)[1],
        );
    }

    public function logout(): JsonResponse
    {
        $user = auth()->user();
        $user->tokens()->delete();

        return $this->successMessageResponse(trans('success'));
    }

    /**
     * Register a user account and return login token with user payload.
     *
     * @unauthenticated
     *
     * @throws Throwable
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $phone = Phone::make($data['phone']);
            $data['phone'] = $phone->toString();
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('users');
            }
            if ($request->filled('password')) {
                $data['password'] = $request->password;
            } else {
                $data['password'] = $data['phone'];
            }
            $user = User::create($data);
            $code = $user->updateOrCreateVerificationCode($this->generateOtpForPhone($phone), 'login');
            $result = $this->smsService->send(
                SmsMessage::otp($code->token),
                $phone->toString()
            );
            Log::channel('sms')->info('Login OTP for user '.$user->id.' is '.$code->token, $result->toArray());
            $token = explode('|', $user->createToken('login', [], now()->addMinutes(15))->plainTextToken)[1];
            $user->load(['nationality.translation']);
            DB::commit();

            return $this->successResponseWithToken(
                UserResource::make($user),
                $token
            );
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(
                trans('something went wrong'),
                400
            );
        }
    }

    public function profileUpdate(UpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = auth()->user();
        if ($request->hasFile('image')) {
            $user->deleteImage();
            $data['image'] = $request->file('image')->store('users');
        }
        $user->update($data);
        $user->load(['nationality.translation']);

        return $this->successResponse(UserResource::make($user));
    }

    public function auth(): JsonResponse
    {
        $user = auth()->user();

        return $this->successResponse(UserResource::make($user));
    }
}
