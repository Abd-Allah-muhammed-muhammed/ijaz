import { Link } from '@inertiajs/react';
import { KTIcon } from '@/vendor/metronic/helpers';
import { cn } from '@/shared/lib/utils';

type Props = {
  href: string;
  title: string;
  icon?: string;
  isActive?: boolean;
  show?: boolean;
};

export function SidebarNavItem({ href, title, icon, isActive = false, show = true }: Props) {
  if (!show) {
    return null;
  }

  return (
    <Link
      href={href}
      className={cn(
        'ds-sidebar-link group flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium transition-colors',
        'text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
        isActive &&
          'bg-sidebar-primary text-sidebar-primary-foreground hover:bg-sidebar-primary hover:text-sidebar-primary-foreground',
      )}
    >
      {icon ? (
        <span
          className={cn(
            'ds-sidebar-icon flex size-6 shrink-0 items-center justify-center',
            isActive ? 'text-sidebar-primary-foreground' : 'text-sidebar-foreground/55 group-hover:text-sidebar-accent-foreground',
          )}
        >
          <KTIcon iconName={icon} className="text-[1.25rem] leading-none" />
        </span>
      ) : null}
      <span className="ds-sidebar-label truncate">{title}</span>
    </Link>
  );
}
