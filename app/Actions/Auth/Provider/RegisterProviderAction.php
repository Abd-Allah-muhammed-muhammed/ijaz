<?php

namespace App\Actions\Auth\Provider;

use App\Contracts\Auth\ProviderRepositoryInterface;
use App\DTOs\Auth\ProviderRegisterResult;
use App\Enums\Providers\ProviderStatusEnum;
use App\Enums\ProviderTypeFilesEnum;
use App\Services\Sms\Phone;
use Illuminate\Http\Request;
use Modules\Wallet\Actions\CreditProviderRegistrationBonusAction;
use RuntimeException;

class RegisterProviderAction
{
    public function __construct(
        private readonly ProviderRepositoryInterface $providerRepository,
    ) {}

    /**
     * Reproduces Frontend\AuthController::store()'s exact current body.
     *
     * Called from WITHIN a DB transaction (boundary stays at the Service level,
     * mirroring UserAuthService::register()). Two distinct failure paths are
     * preserved verbatim:
     *  - Invalid logo upload: reports the RuntimeException itself (matching the
     *    controller's inline report() for this case) and returns a failed()
     *    result carrying the specific message. It does NOT throw, so the caller
     *    can surface the specific 'logo upload failed' message rather than the
     *    generic one. Nothing is persisted before this check, so committing an
     *    empty transaction is observationally identical to the controller's
     *    original explicit DB::rollBack() here.
     *  - Any other Throwable: propagates uncaught so the Service's transaction
     *    wrapper rolls back and re-throws for the controller to report() and map
     *    to the generic failure response (matching the original outer catch).
     *
     * Phone normalization runs here (top of the transaction) rather than before
     * it as in the original controller. It is a pure string transform with no DB
     * or other side effects and happens before any write, so the placement is
     * observationally identical.
     */
    public function handle(array $validatedData, Request $request): ProviderRegisterResult
    {
        $validatedData['phone'] = Phone::make($validatedData['phone'])->toString();

        $logoFile = $request->file('logo');

        if (
            ! $logoFile
            || ! $logoFile->isValid()
            || $logoFile->getError() !== UPLOAD_ERR_OK
            || ! $logoFile->getRealPath()
        ) {
            report(new RuntimeException(
                'Provider registration: logo upload invalid or temp file missing. '
                .'isValid='.var_export($logoFile?->isValid(), true)
                .' error='.$logoFile?->getError()
                .' realPath='.$logoFile?->getRealPath()
                .' size='.$logoFile?->getSize()
            ));

            return ProviderRegisterResult::failed(__('logo upload failed, please try again'));
        }

        $validatedData['logo'] = $logoFile->store('providers', 'public');
        $provider = $this->providerRepository->create([
            ...$validatedData,
            'status' => ProviderStatusEnum::Pending,
        ]);
        $provider->code = date('dmy').$provider->id;
        $provider->save();
        if ($request->hasFile(ProviderTypeFilesEnum::ID_IMAGE->value)) {
            $provider->addMediaFromRequest(ProviderTypeFilesEnum::ID_IMAGE->value)->toMediaCollection(ProviderTypeFilesEnum::ID_IMAGE->value, 'local');
        }
        if ($request->hasFile(ProviderTypeFilesEnum::COMMERCIAL_RECORD->value)) {
            $provider->addMediaFromRequest(ProviderTypeFilesEnum::COMMERCIAL_RECORD->value)->toMediaCollection(ProviderTypeFilesEnum::COMMERCIAL_RECORD->value, 'local');
        }
        if ($request->hasFile(ProviderTypeFilesEnum::IBAN_CERTIFICATION->value)) {
            $provider->addMediaFromRequest(ProviderTypeFilesEnum::IBAN_CERTIFICATION->value)->toMediaCollection(ProviderTypeFilesEnum::IBAN_CERTIFICATION->value, 'local');
        }
        if ($request->hasFile(ProviderTypeFilesEnum::FREELANCER_CERTIFICATION->value)) {
            $provider->addMediaFromRequest(ProviderTypeFilesEnum::FREELANCER_CERTIFICATION->value)->toMediaCollection(ProviderTypeFilesEnum::FREELANCER_CERTIFICATION->value, 'local');
        }
        $categories = collect($validatedData['categories']);
        $provider->categories()->sync($categories->pluck('id')->toArray());
        app(CreditProviderRegistrationBonusAction::class)->handle($provider);

        return ProviderRegisterResult::success($provider);
    }
}
