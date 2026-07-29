<?php

namespace Modules\Classifieds\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class AuthorizeAdvisementOwnerAction
{
    public function handle(Model $advisement, User $user): void
    {
        if ($advisement->user_id !== $user->id || $advisement->user_type !== $user::class) {
            throw new AccessDeniedHttpException;
        }
    }
}
