<?php

namespace App\Repositories\User;

use App\Contracts\User\UserManagementRepositoryInterface;
use App\Enums\Users\UserStatusEnum;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
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

    public function countAll(): int
    {
        return User::query()->count('id');
    }

    /**
     * @return array{total: int, active: int, blocked: int}
     */
    public function statusCounts(): array
    {
        $counts = User::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total' => (int) $counts->sum(),
            'active' => (int) ($counts[UserStatusEnum::Active->value] ?? 0),
            'blocked' => (int) ($counts[UserStatusEnum::Blocked->value] ?? 0),
        ];
    }

    /**
     * @return SupportCollection<string, int>
     */
    public function registrationCountsSince(CarbonInterface $since): SupportCollection
    {
        return User::query()
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');
    }

    /**
     * @return Collection<int, User>
     */
    public function latestForDashboard(int $limit = 4): Collection
    {
        return User::query()
            ->withCount(['orders'])
            ->limit($limit)
            ->orderByDesc('created_at')
            ->get();
    }
}
