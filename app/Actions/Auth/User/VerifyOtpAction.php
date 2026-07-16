<?php

namespace App\Actions\Auth\User;

use App\Contracts\OTPS\HasOTPsContract;
use App\DTOs\Auth\OtpVerifyResult;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Http\Resources\Dashboard\ProviderResource;
use App\Models\Provider;
use App\Models\User;
use App\Models\VerificationCode;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

class VerifyOtpAction
{
    /**
     * Reproduces OtpController::verify() + processCode() + getUserResource()
     * EXACTLY. Returns null for the "wrong OTP" case (invalid/expired/missing
     * code) which the controller maps to failedMessageResponse('wrong OTP');
     * otherwise returns the processCode()-shaped result.
     *
     * The full switch (email/phone/password_reset/login/default->throw) is kept
     * verbatim, including markPhoneAsVerified()'s no-op stub, the login-case
     * player_id update + unreadNotifications count, and the User/Provider
     * resource match (Provider arm kept even though it is dead code in practice).
     *
     * @throws Exception
     */
    public function handle(User $user, string $type, string $otp): ?OtpVerifyResult
    {
        $code = $user->verificationCodes()->where('type', $type)->first();

        if (! $code?->verify($otp)) {
            return null;
        }

        return $this->processCode($code, $user);
    }

    /**
     * @throws Exception
     */
    protected function processCode(VerificationCode $code, HasOTPsContract $model): OtpVerifyResult
    {
        switch ($code->type) {
            case 'email':

                $model = $model->markEmailAsVerified();

                return new OtpVerifyResult(
                    success: true,
                    data: $this->getUserResource($model),
                );

            case 'phone':

                $model->markPhoneAsVerified();

                return new OtpVerifyResult(success: false);

            case 'password_reset':

                return new OtpVerifyResult(success: false);

            case 'login':

                $token = $model->markLoginAsVerified();
                $model->load(['nationality.translation']);
                $model->loadCount('unreadNotifications');
                $model->update([
                    'player_id' => request()->input('player_id', null),
                ]);

                return new OtpVerifyResult(
                    success: true,
                    data: $this->getUserResource($model),
                    token: $token ?? '',
                );

            default:

                throw new Exception('Unknown OTP type: '.$code->type);
        }
    }

    protected function getUserResource(Model $model): ?JsonResource
    {
        return match (get_class($model)) {
            User::class => new UserResource($model),
            Provider::class => new ProviderResource($model),
            default => null,
        };
    }
}
