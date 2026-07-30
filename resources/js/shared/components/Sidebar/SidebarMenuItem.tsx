/**
 * Literal copy of vendor/metronic/.../sidebar-menu/SidebarMenuItem.tsx
 * with mechanical class substitution.
 *
 * ONLY deliberate deviation: active background uses `bg-primary` (app token)
 * instead of Metronic `$app-sidebar-dark-menu-link-bg-color-active` (#1C1C21).
 *
 * Colors use `!` (Tailwind v4 important) so Bootstrap `a { color: link }` cannot
 * paint nav links blue — the failure mode of prior replacement attempts.
 */
import { Link } from '@inertiajs/react';
import clsx from 'clsx';
import { type FC, type ReactNode } from 'react';
import { KTIcon } from '@/vendor/metronic/helpers';
import { useLayout } from '@/vendor/metronic/layout/core';

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

const SidebarMenuItem: FC<Props> = ({
  children,
  to,
  title,
  icon,
  fontIcon,
  hasBullet = false,
  isActive = false,
  show = true,
}) => {
  const { config } = useLayout();
  const { app } = config;

  if (!show) {
    return null;
  }

  return (
    <div className="block py-[0.15rem] ps-[0.115rem]">
      <Link
        className={clsx(
          'group flex w-full items-center rounded-[0.475rem] px-4 py-[0.65rem] no-underline outline-none!',
          'text-[1.1rem] font-semibold',
          'transition-[color] duration-200 ease-[ease]',
          isActive
            ? /* DEVIATION: bg-primary instead of #1C1C21 */
              'bg-primary! text-primary-foreground! hover:bg-primary! hover:text-primary-foreground!'
            : 'bg-transparent text-[#9A9CAE]! hover:bg-transparent hover:text-[#F5F5F5]!',
        )}
        href={to}
      >
        {hasBullet && (
          <span className="me-2 flex w-5 shrink-0 items-center justify-center">
            <span className="size-1.5 rounded-full bg-current"></span>
          </span>
        )}
        {icon && app?.sidebar?.default?.menu?.iconType === 'svg' && (
          <span
            className={clsx(
              'me-2 flex size-8 shrink-0 items-center justify-center',
              isActive
                ? 'text-primary-foreground!'
                : 'text-[#464852]! group-hover:text-[#F5F5F5]!',
            )}
          >
            <KTIcon iconName={icon} className="text-[1.5rem]! leading-none" />
          </span>
        )}
        {fontIcon && app?.sidebar?.default?.menu?.iconType === 'font' && (
          <i
            className={clsx('bi text-[1.35rem]!', fontIcon)}
            style={{
              marginInlineEnd: '0.5rem',
            }}
          ></i>
        )}
        <span className="grow truncate">{title}</span>
      </Link>
      {children}
    </div>
  );
};

export { SidebarMenuItem };
