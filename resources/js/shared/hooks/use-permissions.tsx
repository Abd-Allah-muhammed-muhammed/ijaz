import { createPermissionChecker, type PermissionChecker } from '@/shared/lib/permissions';
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

  return useMemo(
    () => createPermissionChecker(permissions ?? [], user?.roles ?? [], Boolean(user?.root)),
    [permissions, user?.roles, user?.root],
  );
}

export type { PermissionChecker };
