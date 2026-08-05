<?php

namespace App\Support;

use App\Models\Admin;

/**
 * Shared authorization for Pulse, Telescope, Log Viewer, and Horizon.
 *
 * Access is permission-based: any admin granted "view monitoring tools" may use
 * all four tools. Root admins continue to pass via the global Gate::before bypass
 * in AdminServiceProvider (no special-case root check here).
 */
final class MonitoringAccess
{
    public static function allows(Admin $admin): bool
    {
        return $admin->can('view monitoring tools');
    }
}
