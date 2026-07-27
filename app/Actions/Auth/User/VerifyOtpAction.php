<?php

namespace App\Actions\Auth\User;

use App\Contracts\Auth\OtpRepositoryInterface;
use App\Contracts\OTPS\HasOTPsContract;
use App\DTOs\Auth\OtpVerifyResult;
use App\Enums\Auth\OtpPurposeEnum;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Http\Resources\Dashboard\ProviderResource;
use App\Models\Otp;
use App\Models\Provider;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

class VerifyOtpAction
{
    public function __construct(
        private readonly OtpRepositoryInterface $otpRepository,
    ) {}

    /**
     * Reproduces OtpController::verify() + processCode() + getUserResource()
     * EXACTLY. Returns null for the "wrong OTP" case (invalid/expired/missing
     * code) which the controller maps to failedMessageResponse('wrong OTP');
     * otherwise returns the processCode()-shaped result.
     *
     * Purpose-specific side effects stay in processCode() — not collapsed into
     * the repository lookup layer.
     *
     * @throws Exception
     */
    public function handle(User $user, OtpPurposeEnum|string $purpose, string $otp): ?OtpVerifyResult
    {
        $purpose = $purpose instanceof OtpPurposeEnum
            ? $purpose
            : OtpPurposeEnum::from($purpose);

        $code = $this->otpRepository->findForSubject($user, $purpose);

        if (! $code?->matches($otp)) {
            return null;
        }

        return $this->processCode($code, $user);
    }

    /**
     * @throws Exception
     */
    protected function processCode(Otp $code, HasOTPsContract $model): OtpVerifyResult
    {
        return match ($code->purpose) {
            OtpPurposeEnum::Email => $this->processEmail($model),
            OtpPurposeEnum::Phone => $this->processPhone($model),
            OtpPurposeEnum::PasswordReset => new OtpVerifyResult(success: false),
            OtpPurposeEnum::Login => $this->processLogin($model),
            default => throw new Exception('Unknown OTP type: '.$code->purpose->value),
        };
    }

    protected function processEmail(HasOTPsContract $model): OtpVerifyResult
    {
        $model = $model->markEmailAsVerified();

        return new OtpVerifyResult(
            success: true,
            data: $this->getUserResource($model),
        );
    }

    protected function processPhone(HasOTPsContract $model): OtpVerifyResult
    {
        // Side-effect now actually persists (previously a no-op stub), but the
        // response contract is deliberately UNCHANGED (still success: false) to
        // avoid a mobile-breaking change. Making this endpoint return success:true
        // is a deferred product decision — see docs/DEFERRED_MOBILE_BREAKING_CHANGES.md
        $model->markPhoneAsVerified();

        return new OtpVerifyResult(success: false);
    }

    protected function processLogin(HasOTPsContract $model): OtpVerifyResult
    {
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
