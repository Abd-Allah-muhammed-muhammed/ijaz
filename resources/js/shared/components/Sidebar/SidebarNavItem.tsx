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

/**
 * Nav link styled to Metronic dark-sidebar `_sidebar-dark.scss` values:
 * - Rest title `$gray-700-dark` #9A9CAE, icon `$gray-400-dark` #464852
 * - Hover: title/icon → `$gray-900-dark` #F5F5F5, **no** background change
 * - Active (deviation): `bg-primary` / `text-primary-foreground` instead of Metronic `#1C1C21`
 * - Padding `$menu-link-padding-y/x` 0.65rem / 1rem; radius `$border-radius` 0.475rem
 * - Title font 1.1rem / weight 600 (sidebar root override); icon slot 2rem, `fs-2` (1.5rem)
 * - Transition: `$transition-link` → `color .2s ease`
 */
export function SidebarNavItem({ href, title, icon, isActive = false, show = true }: Props) {
  if (!show) {
    return null;
  }

  return (
    <div className="py-[0.15rem] ps-[0.115rem]">
      <Link
        href={href}
        className={cn(
          'ds-sidebar-link group flex w-full items-center rounded-[0.475rem] px-4 py-[0.65rem]',
          'text-[1.1rem] font-semibold leading-normal',
          'transition-[color] duration-200 ease-[ease]',
          isActive
            ? 'bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground'
            : 'bg-transparent text-[#9A9CAE] hover:bg-transparent hover:text-[#F5F5F5]',
        )}
      >
        {icon ? (
          <span
            className={cn(
              'ds-sidebar-icon me-2 flex size-8 shrink-0 items-center justify-center',
              isActive
                ? 'text-primary-foreground'
                : 'text-[#464852] group-hover:text-[#F5F5F5]',
            )}
          >
            {/* Metronic SidebarMenuItem used `fs-2` → 1.5rem */}
            <KTIcon iconName={icon} className="fs-2 !leading-none" />
          </span>
        ) : null}
        <span className="ds-sidebar-label truncate">{title}</span>
      </Link>
    </div>
  );
}
