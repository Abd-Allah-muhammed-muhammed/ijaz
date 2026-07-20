<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Users\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\Api\V1\NationalityResource;
use App\Http\Resources\Dashboard\UserCollection;
use App\Http\Resources\Dashboard\UserResource;
use App\Models\User;
use App\Services\Sms\Phone;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Modules\Geo\Models\Nationality;
use Modules\Wallet\Http\Resources\Dashboard\WalletTransactionCollection;
use Throwable;

class UserController extends Controller implements HasMiddleware
{
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
        $rows = User::with(['wallet', 'latestBlockHistory'])
            ->when($request->input('search'), function ($query, $v) {
                return $query->where(function (Builder $q) use ($v) {
                    $q->where(DB::raw('CONCAT(f_name, " ", l_name)'), 'like', "%{$v}%")
                        ->orWhere('phone', 'like', "%{$v}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/Users/Index', [
            'prams' => $request->all() ?: [],
            'rows' => UserCollection::make($rows),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user)
    {
        $user->load([
            'wallet',
            'nationality',
        ]);

        return inertia('Dashboard/Users/Show', [
            'user' => function () use ($user) {
                return UserResource::make($user);
            },
            'transactions' => WalletTransactionCollection::make(
                $user
                    ->wallet
                    ->transactions()
                    ->latest()
                    ->when($request->input('search'), function ($query, $v) {
                        $query->where(fn (Builder $q) => $q->where('id', 'like', "%{$v}%")->orWhere('operation_id', 'like', "%{$v}%"));
                    })
                    ->paginate($request->integer('per_page', 25))
                    ->withQueryString()
            ),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $user->load([
            'nationality' => function ($query) {
                $query->withTranslation();
            },
        ]);

        return inertia('Dashboard/Users/Edit', [
            'row' => UserResource::make($user),
            'nationalities' => NationalityResource::collection(Nationality::withTranslation()->get()),
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
        $data = $request->validated();

        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('users', 'public');
                $user->deleteImage();
            } else {
                unset($data['image']);
            }
            if (! $request->filled('password')) {
                unset($data['password']);
            }

            $data['phone'] = Phone::make($data['phone'])->toString();

            $user->update($data);
            DB::commit();

            return to_route('dashboard.users.index')->with('success', __('data updated successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(UserRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $data['image'] = $request->file('image')?->store('users', 'public');
            $data['phone'] = Phone::make($data['phone'])->toString();
            User::create($data);
            DB::commit();

            return to_route('dashboard.users.index')->with('success', __('data saved successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/Users/Create', [
            'nationalities' => NationalityResource::collection(Nationality::withTranslation()->get()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws Throwable
     */
    public function destroy(User $user)
    {
        DB::beginTransaction();
        try {
            $user->delete();
            DB::commit();

            return redirect()->route('dashboard.users.index')
                ->with('success', __('data deleted successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }

    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'status' => ['required', new Enum(UserStatusEnum::class)],
            'block_days' => 'nullable|integer',
            'block_reason' => 'nullable|string',
        ]);
        $user->status = $request->status;
        $user->save();
        if ($request->status == UserStatusEnum::Blocked->value) {
            $user->block($request->block_days ?: 0, $request->block_reason);
            $user->tokens()->delete(); // Delete previous login token
        }

        return to_route('dashboard.users.index')->with('success', __('data saved successfully'));
    }
}
