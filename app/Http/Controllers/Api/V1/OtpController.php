<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\OTPS\HasOTPsContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendOTPRequest;
use App\Http\Requests\Api\V1\VerifyOTPRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Http\Resources\Dashboard\ProviderResource;
use App\Models\Provider;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\Sms\Phone;
use App\Traits\OTPGeneration;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Random\RandomException;

class OtpController extends Controller
{
  use HasApiResponse, OTPGeneration;

  /**
   * @throws RandomException
   */
  public function send(SendOTPRequest $request): JsonResponse
  {
    $user = auth()->user();
    $phone = Phone::make($user->phone);
    $user->updateOrCreateVerificationCode($this->generateOtpForPhone($phone), $request->type);

    return $this->successMessageResponse(trans('Otp Has been send'));
  }

  /**
   * @throws Exception
   */
  public function verify(VerifyOTPRequest $request): JsonResponse
  {
    $user = auth()->user();
    $code = $user->verificationCodes()->where('type', $request->type)->first();
    if (! $code?->verify($request->otp)) {
      return $this->failedMessageResponse(trans('wrong OTP'));
    }
    $response = $this->processCode($code, $user);

    return $this->makeResponse(
      $response['success'] ?? false,
      $response['data'] ?? [],
      $response['message'] ?? '',
      $response['errors'] ?? [],
      $response['token'] ?? ''
    );
  }

  /**
   * @throws Exception
   */
  protected function processCode(VerificationCode $code, HasOTPsContract $model): array
  {
    switch ($code->type) {
      case 'email':

        $model = $model->markEmailAsVerified();

        return [
          'success' => true,
          'message' => '',
          'data' => $this->getUserResource($model),
          'errors' => [],
          'token' => '',
        ];

      case 'phone':

        $model->markPhoneAsVerified();

        return [
          'success' => false,
        ];

      case 'password_reset':

        return [
          'success' => false,
        ];

      case 'login':

        $token = $model->markLoginAsVerified();
        $model->load(['nationality.translation']);
        $model->loadCount('unreadNotifications');
        $model->update([
          'player_id' => request()->input('player_id', null),
        ]);

        return [
          'success' => true,
          'message' => '',
          'data' => $this->getUserResource($model),
          'errors' => [],
          'token' => $token,
        ];

      default:

        throw new Exception('Unknown OTP type: ' . $code->type);
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
