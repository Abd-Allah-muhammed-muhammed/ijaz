<?php

namespace App\Actions\Auth\User;

use App\Models\User;
use App\Services\Sms\Phone;
use App\Traits\OTPGeneration;
use Illuminate\Support\Facades\Log;
use Modules\Sms\Services\SmsService;
use Random\RandomException;

class SendLoginOtpAction
{
    use OTPGeneration;

    public function __construct(
        private readonly SmsService $smsService,
    ) {}

    /**
     * Generates, stores, and SENDS an OTP via SMS for login/register — matches
     * the current behavior of AuthController::login()/register() exactly.
     *
     * This is intentionally NOT the same as OtpController::send() (see
     * IssueOtpAction), which does not dispatch SMS or log today.
     *
     * @throws RandomException
     */
    public function handle(User $user): void
    {
        $phone = Phone::make($user->phone);
        $code = $user->updateOrCreateVerificationCode($this->generateOtpForPhone($phone), 'login');
        $result = $this->smsService->sendOtp($code->token, $phone->toString());
        Log::channel('sms')->info('Login OTP for user '.$user->id.' is '.$code->token, $result->toArray());
    }
}
