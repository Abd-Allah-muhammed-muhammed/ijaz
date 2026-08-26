<?php

namespace Modules\Guarantor\Support;

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;

trait GuarantorFirebaseNotifiable
{
    protected function guarantorPartyReceivesFirebase(object $notifiable): bool
    {
        return $notifiable instanceof User
            || $notifiable instanceof Provider;
    }

    protected function guarantorPartyOrAdminReceivesFirebase(object $notifiable): bool
    {
        return $this->guarantorPartyReceivesFirebase($notifiable)
            || $notifiable instanceof Admin;
    }
}
