<?php

namespace App\Actions\Auth\User;

use App\Actions\Auth\EnsureOtpCooldownAction;
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
        private readonly EnsureOtpCooldownAction $ensureOtpCooldownAction,
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
        $normalizedPhone = $phone->toString();

        $this->ensureOtpCooldownAction->ensure($normalizedPhone);

        $code = $user->updateOrCreateVerificationCode($this->generateOtpForPhone($phone), 'login');
        $result = $this->smsService->sendOtp($code->token, $normalizedPhone);

        if ($result->isSuccessful()) {
            $this->ensureOtpCooldownAction->recordSent($normalizedPhone);
        }

        // Do not log the OTP or $result->data: AuthenticaGateway nests the code
        // in data.message.body (SmsMessage::toArray()), which would leak it.
        Log::channel('sms')->info('Login OTP sent for user '.$user->id, [
            'status' => $result->status,
            'driver' => $result->driver,
            'message' => $result->message,
        ]);
    }
}
