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
     * Dispatches an OTP via SMS to the normalized phone, records the cooldown
     * only on confirmed gateway acceptance, and logs safe metadata without the
     * token or gateway response data.
     *
     * Whitelisted SMS_TEST_NUMBER skips the real gateway call in every
     * environment (including production) so debugging never burns SMS credits,
     * while still recording cooldown and preserving the success log shape.
     *
     * When a User is provided (authenticated / existing-user flows), logs
     * "OTP sent for user {id}" with type. When null (pre-persist registration
     * flows such as provider OTP), logs "Login OTP sent for number {phone}"
     * without a type key — preserving each path's historical log shape.
     *
     * Callers must check the cooldown and generate/persist the token before
     * calling this action. This action sends and records success only.
     */
    public function handle(
        string $token,
        string $normalizedPhone,
        string $type,
        ?User $user = null,
    ): SmsResult {
        $result = $this->isWhitelistedTestNumber($normalizedPhone)
            ? $this->skippedTestNumberResult($normalizedPhone)
            : $this->smsService->sendOtp($token, $normalizedPhone);

        if ($result->isSuccessful()) {
            $this->ensureOtpCooldownAction->recordSent($normalizedPhone);
        }

        if ($user !== null) {
            Log::channel('sms')->info('OTP sent for user '.$user->id, [
                'type' => $type,
                'status' => $result->status,
                'driver' => $result->driver,
                'message' => $result->message,
            ]);
        } else {
            Log::channel('sms')->info('Login OTP sent for number '.$normalizedPhone, [
                'status' => $result->status,
                'driver' => $result->driver,
                'message' => $result->message,
            ]);
        }

        return $result;
    }

    private function isWhitelistedTestNumber(string $normalizedPhone): bool
    {
        $testNumber = config('sms.test_number');

        return filled($testNumber) && $normalizedPhone === $testNumber;
    }

    private function skippedTestNumberResult(string $normalizedPhone): SmsResult
    {
        return new SmsResult(
            status: 'success',
            driver: 'test_number',
            message: 'Skipped real SMS send for whitelisted test number',
            data: [
                'number' => $normalizedPhone,
                'skipped' => true,
            ],
        );
    }
}
