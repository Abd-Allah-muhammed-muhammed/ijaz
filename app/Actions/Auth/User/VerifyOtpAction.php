<?php

namespace App\Actions\Auth\User;

use App\Contracts\Auth\HasOTPsContract;
use App\Contracts\Auth\OtpRepositoryInterface;
use App\Contracts\Auth\OtpSessionRepositoryInterface;
use App\DTOs\Auth\OtpVerifyResult;
use App\Enums\Auth\OtpPurposeEnum;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Http\Resources\Dashboard\ProviderResource;
use App\Models\Otp;
use App\Models\OtpSession;
use App\Models\Provider;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class VerifyOtpAction
{
    public function __construct(
        private readonly OtpRepositoryInterface $otpRepository,
        private readonly OtpSessionRepositoryInterface $otpSessionRepository,
    ) {}

    /**
     * Public OtpSession-based verify for login/register challenges.
     *
     * Missing and expired sessions both return verification_expired (no
     * distinct not_found) to avoid leaking whether a verification_id existed.
     *
     * @throws Exception
     */
    public function handleSession(string $verificationId, string $code, ?string $playerId = null): OtpVerifyResult
    {
        $session = $this->otpSessionRepository->findById($verificationId);

        if (! $session || $session->isExpired()) {
            return OtpVerifyResult::failure('verification_expired', trans('verification expired'));
        }

        if ($session->hasExceededAttempts()) {
            return OtpVerifyResult::failure('max_attempts_exceeded', trans('max attempts exceeded'));
        }

        /** @var User $user */
        $user = $session->user;
        $otp = $this->otpRepository->findForSubject($user, $session->purpose);

        if (! $otp?->matches($code)) {
            $this->logCollapsedVerifyFailure($session, $user, $otp);

            $session = $this->otpSessionRepository->incrementAttempts($session);
            $remaining = max(0, $session->max_attempts - $session->attempts_count);

            if ($session->hasExceededAttempts()) {
                return OtpVerifyResult::failure('max_attempts_exceeded', trans('max attempts exceeded'));
            }

            // User-facing message stays ambiguous on purpose (security).
            return OtpVerifyResult::failure(
                'invalid_code',
                trans('wrong OTP'),
                $remaining,
            );
        }

        return $this->completeSession($session, $user, $playerId);
    }

    /**
     * Distinguish missing / expired / wrong-code internally — all three still
     * surface as the same user-facing invalid_code response.
     */
    private function logCollapsedVerifyFailure(OtpSession $session, User $user, ?Otp $otp): void
    {
        $reason = match (true) {
            $otp === null => 'missing_otp',
            $otp->isExpired() => 'expired_otp',
            default => 'invalid_code',
        };

        Log::channel('sms')->warning('OTP session verify failed', [
            'reason' => $reason,
            'verification_id' => (string) $session->id,
            'user_id' => $user->id,
            'purpose' => $session->purpose->value,
        ]);
    }

    /**
     * Authenticated purpose-based verify (phone / email / password_reset / …).
     *
     * @throws Exception
     */
    public function handle(User $user, OtpPurposeEnum|string $purpose, string $otp): ?OtpVerifyResult
    {
        $purpose = $purpose instanceof OtpPurposeEnum
            ? $purpose
            : OtpPurposeEnum::from($purpose);

        if (in_array($purpose, OtpPurposeEnum::sessionChallengeCases(), true)) {
            return null;
        }

        $code = $this->otpRepository->findForSubject($user, $purpose);

        if (! $code?->matches($otp)) {
            return null;
        }

        return $this->processCode($code, $user);
    }

    protected function completeSession(OtpSession $session, User $user, ?string $playerId): OtpVerifyResult
    {
        $purpose = $session->purpose;

        // Re-check live status — account may have been banned/deleted after OTP was sent.
        $user->refresh();
        $rejectionMessage = $user->status->authRejectionMessage((bool) $user->blocked_until);

        if ($rejectionMessage !== null) {
            $this->otpSessionRepository->deleteForUser($user, $purpose);
            $this->otpRepository->deleteForSubject($user, $purpose);
            $user->tokens()->delete();

            return OtpVerifyResult::failure(
                'account_inactive',
                $rejectionMessage,
                statusCode: 400,
            );
        }

        $this->otpSessionRepository->deleteForUser($user, $purpose);
        $this->otpRepository->deleteForSubject($user, $purpose);

        $plainTextToken = $user->createToken('user-app', ['*'])->plainTextToken;
        $accessToken = explode('|', $plainTextToken)[1];

        $user->load(['nationality.translation']);
        $user->loadCount('unreadNotifications');

        if (filled($playerId)) {
            $user->registerDeviceToken($playerId);
        }

        return OtpVerifyResult::sessionSuccess(
            $accessToken,
            new UserResource($user->fresh()->load(['nationality.translation'])),
        );
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
            OtpPurposeEnum::Login, OtpPurposeEnum::Register => throw new Exception(
                'Session-challenge purposes must use handleSession()'
            ),
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
        // Side-effect persists; response contract stays success:false (deferred Item 4).
        $model->markPhoneAsVerified();

        return new OtpVerifyResult(success: false);
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
