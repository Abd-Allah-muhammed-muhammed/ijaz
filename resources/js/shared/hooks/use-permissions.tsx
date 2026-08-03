import { createPermissionChecker, roleNamesFromAuth, type PermissionChecker, type RoleLike } from '@/shared/lib/permissions';
import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

/**
 * Dashboard permission/role checks from Inertia `auth` shared props.
 *
 * ```tsx
 * const { hasPermission, hasAnyPermission } = usePermissions();
 * if (hasPermission('create propertyTypes')) { ... }
 * ```
 *
 * Root users (`auth.user.root`) pass every check.
 * Pure logic lives in `@/shared/lib/permissions` for non-React use / tests.
 */
export default function usePermissions(): PermissionChecker {
  const { user, permissions } = usePage().props.auth;

  const roleNames = useMemo(
    () => roleNamesFromAuth(user?.roles as RoleLike[] | undefined),
    [user?.roles],
  );

  return useMemo(
    () => createPermissionChecker(permissions ?? [], roleNames, Boolean(user?.root)),
    [permissions, roleNames, user?.root],
  );
}

export type { PermissionChecker };
