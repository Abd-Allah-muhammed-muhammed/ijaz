import {usePage} from '@inertiajs/react';
import {useMemo} from 'react';

const usePermissions = () => {

  const {user,permissions} = usePage().props.auth;
  const userPermissions = useMemo<string[]>(
    () => permissions,
    [permissions],
  );
  const roles = useMemo<string[]>(() => user.roles, [user.roles]);
  return {
    hasPermission: function (permission: string) {
      if (user.root){
        return true;
      }
      return userPermissions.includes(permission);
    },
    hasRole: (role: string) => {
      if (user.root){
        return true;
      }
      return roles.includes(role);
    },
    hasAnyPermission: function (permissions: string[]) {
      if (user.root){
        return true;
      }
      return permissions.some((permission) => {
        return userPermissions.includes(permission);
      });
    },
    hasAnyRole: function (roles: string[]) {
      if (user.root){
        return true;
      }
      return roles.some((role) => {
        return this.hasRole(role);
      });
    },
    hasAllPermissions: function (permissions: string[]) {
      if (user.root){
        return true;
      }
      return permissions.every((permission) => {
        return this.hasPermission(permission);
      });
    },
    hasAllRole: function (roles: string[]) {
      if (user.root){
        return true;
      }
      return roles.every((role) => {
        return this.hasRole(role);
      });
    },
  };
};
export default usePermissions;
