<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Auth\OtpVerifyResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ResendOtpSessionRequest;
use App\Http\Requests\Api\V1\SendOTPRequest;
use App\Http\Requests\Api\V1\VerifyOTPRequest;
use App\Http\Requests\Api\V1\VerifyOtpSessionRequest;
use App\Services\Auth\UserAuthService;
use Dedoc\Scramble\Attributes\Group;
use Exception;
use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Random\RandomException;

#[Group('Auth')]
class OtpController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly UserAuthService $userAuthService,
    ) {}

    /**
     * @throws RandomException
     */
    public function send(SendOTPRequest $request): JsonResponse
    {
        $this->userAuthService->sendOtp($request->type);

        return $this->successMessageResponse(trans('Otp Has been send'));
    }

    /**
     * Public OtpSession verify for login/register (verification_id + code).
     *
     * @unauthenticated
     *
     * @throws Exception
     */
    public function verify(VerifyOtpSessionRequest $request): JsonResponse
    {
        $result = $this->userAuthService->verifyOtpSession(
            $request->string('verification_id')->toString(),
            $request->string('code')->toString(),
            $request->input('player_id'),
        );

        return $this->mapVerifyResult($result);
    }

    /**
     * Authenticated purpose-based OTP verify (phone / email / …).
     *
     * @throws Exception
     */
    public function verifyPurpose(VerifyOTPRequest $request): JsonResponse
    {
        $result = $this->userAuthService->verifyOtp($request->type, $request->otp);

        if ($result === null) {
            return $this->failedMessageResponse(trans('wrong OTP'));
        }

        return $this->makeResponse(
            $result->success,
            $result->data ?? [],
            $result->message,
            $result->errors,
            $result->token
        );
    }

    /**
     * Resend OTP for an existing OtpSession (same verification_id).
     *
     * @unauthenticated
     *
     * @throws RandomException
     */
    public function resend(ResendOtpSessionRequest $request): JsonResponse
    {
        $result = $this->userAuthService->resendOtpSession(
            $request->string('verification_id')->toString(),
        );

        if ($result instanceof OtpVerifyResult) {
            return $this->mapVerifyResult($result);
        }

        return $this->successResponse($result->toData());
    }

    private function mapVerifyResult(OtpVerifyResult $result): JsonResponse
    {
        if (! $result->success) {
            return $this->makeResponse(
                false,
                $result->toErrorData(),
                $result->message,
                [],
                '',
                $result->statusCode,
            );
        }

        if ($result->accessToken !== '') {
            return $this->makeResponse(
                true,
                $result->toSuccessData(),
                $result->message,
                [],
                $result->accessToken,
            );
        }

        return $this->makeResponse(
            $result->success,
            $result->data ?? [],
            $result->message,
            $result->errors,
            $result->token
        );
    }
}
