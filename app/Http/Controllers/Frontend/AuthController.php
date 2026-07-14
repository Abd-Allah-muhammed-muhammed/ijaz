<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\Providers\ProviderStatusEnum;
use App\Enums\ProviderTypeFilesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderRegisterRequest;
use App\Http\Resources\Dashboard\CategoryResource;
use App\Http\Resources\Dashboard\CityResource;
use App\Http\Resources\Dashboard\ProviderTypeResource;
use App\Http\Resources\Dashboard\RegionResource;
use App\Models\Category;
use App\Models\City;
use App\Models\Provider;
use App\Models\ProviderType;
use App\Models\Region;
use App\Models\RegisterVerificationCode;
use App\Rules\ValidPhoneRule;
use App\Services\Sms\Phone;
use App\Traits\OTPGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\Services\SmsService;
use Modules\Wallet\Actions\CreditProviderRegistrationBonusAction;
use Random\RandomException;
use RuntimeException;
use Throwable;

class AuthController extends Controller
{
    use OTPGeneration;

    public function __construct(
        private readonly SmsService $smsService,
    ) {}

    /**
     * @throws Throwable
     */
    public function store(ProviderRegisterRequest $request): RedirectResponse
    {

        $data = $request->validated();
        $data['phone'] = Phone::make($data['phone'])->toString();
        DB::beginTransaction();
        try {
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
                DB::rollBack();

                return redirect()->back()->with('error', __('logo upload failed, please try again'));
            }

            $data['logo'] = $logoFile->store('providers', 'public');
            $provider = Provider::create([
                ...$data,
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
            $categories = collect($data['categories']);
            $provider->categories()->sync($categories->pluck('id')->toArray());
            //      $provider->skills()->sync(
            //        $categories
            //          ->map(function ($item) {
            //            return array_map(fn($skill) => ['category_id' => $item['id'], 'skill_id' => $skill], $item['skills']);
            //          })
            //          ->flatten(1)
            //          ->toArray()
            //      );
            app(CreditProviderRegistrationBonusAction::class)->handle($provider);
            DB::commit();

            return to_route('auth.register')
                ->with('success', __('data saved successfully'))
                ->with('id', $provider->id);
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function create()
    {
        return inertia('Frontend/Auth/Register_', [
            'types' => ProviderTypeResource::collection(ProviderType::withTranslation()->get()),
            'regions' => RegionResource::collection(Region::withTranslation()->get()),
            //      'regions' => [],
            'cities' => CityResource::collection(City::withTranslation()->get()),
            //      'cities' => [],
            //      'categories' => CategoryResource::collection(
            //        Category::whereDoesntHave('parent')
            //          ->with('translation')
            //          ->with('childrenRecursive.translation')
            //          ->get()
            //      )
        ]);
    }

    /**
     * @throws RandomException
     */
    public function otp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'numeric', 'digits_between:8,15', new ValidPhoneRule(new Provider)],
        ], [], [
            'phone' => __('phone'),
        ]);
        $phone = Phone::make($request->phone)->toString();
        $code = RegisterVerificationCode::updateOrCreate([
            'queryable' => $phone,
        ], [
            'token' => $this->generateOtpForPhone($phone),
            'expires_at' => now()->addMinutes(5),
        ]);
        $result = $this->smsService->send(
            SmsMessage::otp($code->token),
            $phone
        );
        Log::channel('sms')
            ->info(
                'Login OTP for number '.$phone.' is '.$code->token,
                $result->toArray()
            );

        return response()->json([]);
    }
}
