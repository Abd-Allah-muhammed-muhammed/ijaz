<?php

namespace App\Http\Controllers\Dashboard;

use App\DTOs\User\StoreUserDTO;
use App\DTOs\User\UpdateUserDTO;
use App\DTOs\User\UpdateUserStatusDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UserStatusRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\Api\V1\NationalityResource;
use App\Http\Resources\Dashboard\UserCollection;
use App\Http\Resources\Dashboard\UserResource;
use App\Models\User;
use App\Services\User\UserManagementService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Wallet\Http\Resources\Dashboard\WalletTransactionCollection;
use Throwable;

class UserController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly UserManagementService $userService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show users', only: ['index', 'show']),
            new Middleware('permission:create users', only: ['create', 'store']),
            new Middleware('permission:edit users', only: ['edit', 'update']),
            new Middleware('permission:delete users', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return inertia('Dashboard/Users/Index', [
            'prams' => $request->all() ?: [],
            'rows' => UserCollection::make($this->userService->index($request)),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user)
    {
        $user = $this->userService->show($user);

        return inertia('Dashboard/Users/Show', [
            'user' => function () use ($user) {
                return UserResource::make($user);
            },
            'transactions' => WalletTransactionCollection::make(
                $this->userService->listWalletTransactions(
                    $user,
                    $request->input('search'),
                    $request->integer('per_page', 25),
                )
            ),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return inertia('Dashboard/Users/Edit', [
            'row' => UserResource::make($this->userService->edit($user)),
            'nationalities' => NationalityResource::collection($this->userService->getNationalitiesForDropdown()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function update(UserRequest $request, User $user)
    {
        try {
            $this->userService->update($user, UpdateUserDTO::fromValidated(
                $request->validated(),
                $request->file('image'),
            ));
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }

        return to_route('dashboard.users.index')->with('success', __('data updated successfully'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(UserRequest $request)
    {
        try {
            $this->userService->store(StoreUserDTO::fromValidated(
                $request->validated(),
                $request->file('image'),
            ));
        } catch (Exception $e) {
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }

        return to_route('dashboard.users.index')->with('success', __('data saved successfully'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/Users/Create', [
            'nationalities' => NationalityResource::collection($this->userService->getNationalitiesForDropdown()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws Throwable
     */
    public function destroy(User $user)
    {
        try {
            $this->userService->destroy($user);
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }

        return redirect()->route('dashboard.users.index')
            ->with('success', __('data deleted successfully'));
    }

    public function updateStatus(UserStatusRequest $request, User $user): RedirectResponse
    {
        $this->userService->updateStatus($user, UpdateUserStatusDTO::fromValidated($request->validated()));

        return to_route('dashboard.users.index')->with('success', __('data saved successfully'));
    }
}
