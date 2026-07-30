import { useAdminShell } from '@/apps/admin/layouts/shell-context';
import { cn } from '@/shared/lib/utils';
import { Link } from '@inertiajs/react';
import { KTIcon } from '@/vendor/metronic/helpers';
import type { FC, ReactNode } from 'react';

type Props = {
  to: string;
  title: string;
  icon?: string;
  fontIcon?: string;
  hasBullet?: boolean;
  show?: boolean;
  children?: ReactNode;
  isActive?: boolean;
};

export const SidebarMenuItem: FC<Props> = ({
  children,
  to,
  title,
  icon,
  hasBullet = false,
  isActive = false,
  show = true,
}) => {
  const { collapsed, setMobileOpen } = useAdminShell();

  if (!show) {
    return null;
  }

  return (
    <div className="px-2">
      <Link
        href={to}
        title={collapsed ? title : undefined}
        onClick={() => setMobileOpen(false)}
        className={cn(
          'group relative flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium no-underline outline-none!',
          'transition-colors duration-150 ease-out',
          isActive
            ? 'bg-primary text-primary-foreground shadow-sm'
            : 'text-[var(--admin-shell-sidebar-muted)] hover:bg-[var(--admin-shell-sidebar-hover)] hover:text-[var(--admin-shell-sidebar-foreground)]',
          collapsed && 'justify-center px-0',
        )}
      >
        {isActive && (
          <span
            aria-hidden
            className="absolute inset-y-1 start-0 w-0.5 rounded-full bg-primary-foreground/80"
          />
        )}
        {hasBullet && (
          <span className="flex size-5 shrink-0 items-center justify-center">
            <span className="size-1.5 rounded-full bg-current" />
          </span>
        )}
        {icon && (
          <span
            className={cn(
              'flex size-5 shrink-0 items-center justify-center',
              isActive
                ? 'text-primary-foreground'
                : 'text-[var(--admin-shell-sidebar-muted)] group-hover:text-[var(--admin-shell-sidebar-foreground)]',
            )}
          >
            <KTIcon iconName={icon} className="text-[1.2rem]! leading-none" />
          </span>
        )}
        {!collapsed && <span className="grow truncate">{title}</span>}
      </Link>
      {children}
    </div>
  );
};
