<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\LoginRequest;
use App\Http\Requests\Api\V1\User\RegisterRequest;
use App\Http\Requests\Api\V1\User\UpdateRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Services\Auth\UserAuthService;
use App\Support\Phone;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Random\RandomException;
use Throwable;

#[Group('Auth')]
class AuthController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly UserAuthService $userAuthService,
    ) {}

    /**
     * Start a login OTP challenge (returns verification_id — no Sanctum token).
     *
     * @unauthenticated
     *
     * @throws RandomException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->userAuthService->login($request->phone);

        if (! $result->success) {
            return $this->failedMessageResponse($result->message, $result->statusCode);
        }

        return $this->successResponse($result->toData());
    }

    public function logout(): JsonResponse
    {
        $this->userAuthService->logout();

        return $this->successMessageResponse(trans('success'));
    }

    /**
     * Register and start an OTP challenge (same shape as login — no token / user payload).
     *
     * @unauthenticated
     *
     * @throws Throwable
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->userAuthService->register($request->validated());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(
                trans('something went wrong'),
                400
            );
        }

        return $this->successResponse($result->toData());
    }

    public function profileUpdate(UpdateRequest $request): JsonResponse
    {
        // validated() only includes keys present under `sometimes` rules — never nulls omitted fields.
        $data = $request->safe()->except(['password_confirmation', 'image']);
        $user = auth()->user();

        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }

        if (array_key_exists('phone', $data)) {
            $data['phone'] = Phone::make($data['phone'])->toString();
        }

        if ($request->hasFile('image')) {
            $user->deleteImage();
            $data['image'] = $request->file('image')->store('users', 'public');
        }

        if ($data !== []) {
            $user->update($data);
        }

        $user->load(['nationality.translation']);

        return $this->successResponse(UserResource::make($user));
    }

    public function auth(): JsonResponse
    {
        $user = auth()->user();

        return $this->successResponse(UserResource::make($user));
    }
}
