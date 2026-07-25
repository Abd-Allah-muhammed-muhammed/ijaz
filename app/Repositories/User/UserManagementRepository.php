<?php

namespace App\Repositories\User;

use App\Contracts\User\UserManagementRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserManagementRepository implements UserManagementRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return User::query()
            ->with(['wallet', 'latestBlockHistory'])
            ->when($request->input('search'), function ($query, $v) {
                return $query->where(function (Builder $q) use ($v) {
                    $q->where(DB::raw('CONCAT(f_name, " ", l_name)'), 'like', "%{$v}%")
                        ->orWhere('phone', 'like', "%{$v}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function loadForShow(User $user): User
    {
        return $user->load([
            'wallet',
            'nationality',
        ]);
    }

    public function loadForEdit(User $user): User
    {
        return $user->load([
            'nationality' => function ($query) {
                $query->withTranslation();
            },
        ]);
    }

    public function saveStatus(User $user, string $status): User
    {
        $user->status = $status;
        $user->save();

        return $user;
    }

    public function block(User $user, int $blockDays, ?string $reason): void
    {
        $user->block($blockDays, $reason);
    }

    public function revokeTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
