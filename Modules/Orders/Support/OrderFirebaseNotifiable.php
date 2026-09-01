<?php

namespace Modules\Orders\Support;

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;

trait OrderFirebaseNotifiable
{
    protected function orderPartyReceivesFirebase(object $notifiable): bool
    {
        return $notifiable instanceof User
            || $notifiable instanceof Provider;
    }

    protected function orderPartyOrAdminReceivesFirebase(object $notifiable): bool
    {
        return $this->orderPartyReceivesFirebase($notifiable)
            || $notifiable instanceof Admin;
    }
}
