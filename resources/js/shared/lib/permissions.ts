/**
 * Pure permission/role checks (no React). Used by `usePermissions`.
 *
 * Permission strings match Spatie-style names from Inertia `auth.permissions`
 * (e.g. `"create propertyTypes"`, `"edit propertyTypes"`).
 */

export type PermissionChecker = {
  hasPermission: (permission: string) => boolean;
  hasRole: (role: string) => boolean;
  hasAnyPermission: (permissions: readonly string[]) => boolean;
  hasAnyRole: (roles: readonly string[]) => boolean;
  hasAllPermissions: (permissions: readonly string[]) => boolean;
  hasAllRoles: (roles: readonly string[]) => boolean;
  /** @deprecated Use `hasAllRoles` */
  hasAllRole: (roles: readonly string[]) => boolean;
};

export function createPermissionChecker(
  permissions: readonly string[],
  roles: readonly string[],
  isRoot = false,
): PermissionChecker {
  const hasPermission = (permission: string): boolean => {
    if (isRoot) {
      return true;
    }

    return permissions.includes(permission);
  };

  const hasRole = (role: string): boolean => {
    if (isRoot) {
      return true;
    }

    return roles.includes(role);
  };

  const hasAnyPermission = (required: readonly string[]): boolean => {
    if (isRoot) {
      return true;
    }

    return required.some((permission) => permissions.includes(permission));
  };

  const hasAnyRole = (required: readonly string[]): boolean => {
    if (isRoot) {
      return true;
    }

    return required.some((role) => roles.includes(role));
  };

  const hasAllPermissions = (required: readonly string[]): boolean => {
    if (isRoot) {
      return true;
    }

    return required.every((permission) => permissions.includes(permission));
  };

  const hasAllRoles = (required: readonly string[]): boolean => {
    if (isRoot) {
      return true;
    }

    return required.every((role) => roles.includes(role));
  };

  return {
    hasPermission,
    hasRole,
    hasAnyPermission,
    hasAnyRole,
    hasAllPermissions,
    hasAllRoles,
    hasAllRole: hasAllRoles,
  };
}
