<?php

namespace App\Support;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;

/**
 * Shared authorization for Pulse, Telescope, and Log Viewer.
 *
 * Access requires an admin-guard session that is root OR has the super-admin role,
 * plus the "view monitoring tools" permission (extra layer — permission alone is not enough).
 */
final class MonitoringAccess
{
    public static function allows(): bool
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin instanceof Admin) {
            return false;
        }

        if (! $admin->root && ! $admin->hasRole('super-admin')) {
            return false;
        }

        return $admin->can('view monitoring tools');
    }
}
