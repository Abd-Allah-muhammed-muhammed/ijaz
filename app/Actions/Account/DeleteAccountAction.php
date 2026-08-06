<?php

namespace App\Actions\Account;

use App\Actions\DeviceToken\ClearAllDeviceTokensAction;
use App\Contracts\Account\AccountRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class DeleteAccountAction
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
        private readonly ClearAllDeviceTokensAction $clearAllDeviceTokensAction,
    ) {}

    public function handle(Model $user): void
    {
        $this->repository->markAccountDeleted($user);
        $this->repository->revokeTokens($user);
        $this->clearAllDeviceTokensAction->handle($user);
    }
}
