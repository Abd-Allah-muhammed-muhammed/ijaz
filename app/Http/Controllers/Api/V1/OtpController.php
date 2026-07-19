<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendOTPRequest;
use App\Http\Requests\Api\V1\VerifyOTPRequest;
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
     * @throws Exception
     */
    public function verify(VerifyOTPRequest $request): JsonResponse
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
}
