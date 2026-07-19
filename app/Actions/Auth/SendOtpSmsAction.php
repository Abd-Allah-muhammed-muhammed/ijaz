<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Sms\DTOs\SmsResult;
use Modules\Sms\Services\SmsService;

class SendOtpSmsAction
{
    public function __construct(
        private readonly SmsService $smsService,
        private readonly EnsureOtpCooldownAction $ensureOtpCooldownAction,
    ) {}

    /**
     * Dispatches an OTP via SMS to the user's normalized phone, records the
     * cooldown only on confirmed gateway acceptance, and logs safe metadata
     * without the token or gateway response data.
     *
     * Callers must check the cooldown and generate/persist the token before
     * calling this action. This action sends and records success only.
     */
    public function handle(User $user, string $token, string $normalizedPhone, string $type): SmsResult
    {
        $result = $this->smsService->sendOtp($token, $normalizedPhone);

        if ($result->isSuccessful()) {
            $this->ensureOtpCooldownAction->recordSent($normalizedPhone);
        }

        Log::channel('sms')->info('OTP sent for user '.$user->id, [
            'type' => $type,
            'status' => $result->status,
            'driver' => $result->driver,
            'message' => $result->message,
        ]);

        return $result;
    }
}
