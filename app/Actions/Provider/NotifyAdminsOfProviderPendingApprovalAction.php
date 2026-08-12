<?php

namespace App\Actions\Provider;

use App\Contracts\Auth\AdminRepositoryInterface;
use App\Models\Provider;
use App\Notifications\ProviderPendingApprovalNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Fans out ProviderPendingApprovalNotification to Admins who can view providers
 * (`show providers`), matching dashboard ProviderController authorization.
 */
class NotifyAdminsOfProviderPendingApprovalAction
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function handle(Provider $provider): void
    {
        $admins = $this->adminRepository->getWithPermission('show providers');

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new ProviderPendingApprovalNotification($provider));
    }
}
