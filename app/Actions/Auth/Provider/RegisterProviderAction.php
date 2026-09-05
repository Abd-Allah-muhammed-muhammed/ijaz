<?php

namespace App\Actions\Auth\Provider;

use App\Actions\Provider\SyncProviderCategoriesAndSkillsAction as SyncProviderCategoriesAndSkills;
use App\Contracts\Auth\ProviderRegistrationUploadRepositoryInterface;
use App\Contracts\Auth\ProviderRepositoryInterface;
use App\DTOs\Auth\ProviderRegisterResult;
use App\Enums\Providers\ProviderStatusEnum;
use App\Enums\ProviderTypeFilesEnum;
use App\Models\ProviderRegistrationUpload;
use App\Support\Auth\ProviderRegistrationFileRules;
use App\Support\Phone;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class RegisterProviderAction
{
    public function __construct(
        private readonly ProviderRepositoryInterface $providerRepository,
        private readonly SyncProviderCategoriesAndSkills $syncProviderCategoriesAndSkillsAction,
        private readonly ResolveProviderRegistrationUploadsAction $resolveProviderRegistrationUploadsAction,
        private readonly DeleteProviderRegistrationUploadAction $deleteProviderRegistrationUploadAction,
        private readonly ProviderRegistrationUploadRepositoryInterface $providerRegistrationUploadRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $validatedData
     *
     * @throws Throwable
     * @throws ValidationException
     */
    public function handle(array $validatedData): ProviderRegisterResult
    {
        $validatedData['phone'] = Phone::make($validatedData['phone'])->toString();

        $token = (string) $validatedData['upload_token'];
        /** @var array<string, int|string> $uploadRefs */
        $uploadRefs = $validatedData['uploads'] ?? [];

        $resolved = $this->resolveProviderRegistrationUploadsAction->handle($token, $uploadRefs);

        $logoUpload = $resolved[ProviderRegistrationFileRules::LOGO_FIELD] ?? null;

        if (! $logoUpload instanceof ProviderRegistrationUpload) {
            throw ValidationException::withMessages([
                'uploads.logo' => [__('provider_registration.upload_reference_missing', [
                    'field' => __('logo'),
                ])],
            ]);
        }

        $tempDisk = (string) config('provider_registration.temp_disk');
        $logoAbsolutePath = Storage::disk($tempDisk)->path($logoUpload->path);

        if (! is_file($logoAbsolutePath)) {
            report(new RuntimeException(
                'Provider registration: temp logo missing at path '.$logoUpload->path
            ));

            return ProviderRegisterResult::failed(__('logo upload failed, please try again'));
        }

        $publicDisk = Storage::disk('public');
        $logoStoredPath = 'providers/'.basename($logoUpload->path);
        $logoContents = Storage::disk($tempDisk)->get($logoUpload->path);

        if ($logoContents === null) {
            return ProviderRegisterResult::failed(__('logo upload failed, please try again'));
        }

        $publicDisk->put($logoStoredPath, $logoContents);

        try {
            $validatedData['logo'] = $logoStoredPath;
            unset($validatedData['upload_token'], $validatedData['uploads']);

            $provider = $this->providerRepository->create([
                ...$validatedData,
                'status' => ProviderStatusEnum::Pending,
            ]);
            $provider->code = date('dmy').$provider->id;
            $provider->save();

            foreach (ProviderTypeFilesEnum::cases() as $file) {
                $certificate = $resolved[$file->value] ?? null;

                if (! $certificate instanceof ProviderRegistrationUpload) {
                    continue;
                }

                $absolute = Storage::disk($tempDisk)->path($certificate->path);

                if (! is_file($absolute)) {
                    throw ValidationException::withMessages([
                        "uploads.{$file->value}" => [__('provider_registration.upload_reference_missing', [
                            'field' => __($file->value),
                        ])],
                    ]);
                }

                $provider
                    ->addMedia($absolute)
                    ->usingName(pathinfo($certificate->original_name, PATHINFO_FILENAME))
                    ->usingFileName($certificate->original_name)
                    ->toMediaCollection($file->value, 'local');
            }

            $this->syncProviderCategoriesAndSkillsAction->handle(
                $provider,
                $validatedData['categories'],
            );

            foreach ($resolved as $upload) {
                $this->deleteProviderRegistrationUploadAction->deleteTempFile($upload);
                $this->providerRegistrationUploadRepository->delete($upload);
            }

            return ProviderRegisterResult::success($provider);
        } catch (Throwable $throwable) {
            if ($publicDisk->exists($logoStoredPath)) {
                $publicDisk->delete($logoStoredPath);
            }

            throw $throwable;
        }
    }
}
