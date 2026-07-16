<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\LoginRequest;
use App\Http\Requests\Api\V1\User\RegisterRequest;
use App\Http\Requests\Api\V1\User\UpdateRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Services\Auth\UserAuthService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
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
     * Authenticate a user and return a short-lived login token.
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

        return $this->successResponseWithToken([], $result->token);
    }

    public function logout(): JsonResponse
    {
        $this->userAuthService->logout();

        return $this->successMessageResponse(trans('success'));
    }

    /**
     * Register a user account and return login token with user payload.
     *
     * @unauthenticated
     *
     * @throws Throwable
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->userAuthService->register($request->validated());
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->failedMessageResponse(
                trans('something went wrong'),
                400
            );
        }

        return $this->successResponseWithToken(
            UserResource::make($result->user),
            $result->token
        );
    }

    public function profileUpdate(UpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = auth()->user();
        if ($request->hasFile('image')) {
            $user->deleteImage();
            $data['image'] = $request->file('image')->store('users');
        }
        $user->update($data);
        $user->load(['nationality.translation']);

        return $this->successResponse(UserResource::make($user));
    }

    public function auth(): JsonResponse
    {
        $user = auth()->user();

        return $this->successResponse(UserResource::make($user));
    }
}
