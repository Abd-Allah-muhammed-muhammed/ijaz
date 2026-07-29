import { Link } from '@inertiajs/react';
import clsx from 'clsx';
import { FC } from 'react';
import { KTIcon, WithChildren } from '../../../../helpers';
import { useLayout } from '../../../core';

type Props = {
  to: string;
  title: string;
  icon?: string;
  fontIcon?: string;
  hasBullet?: boolean;
  show?: boolean;
};

type SidebarMenuItemProps = {
  isActive?: boolean;
};
const SidebarMenuItem: FC<Props & WithChildren & SidebarMenuItemProps> = ({
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
    <div className="menu-item">
      <Link className={clsx('menu-link without-sub', { active: isActive })} href={to}>
        {hasBullet && (
          <span className="menu-bullet">
            <span className="bullet bullet-dot"></span>
          </span>
        )}
        {icon && app?.sidebar?.default?.menu?.iconType === 'svg' && (
          <span className="menu-icon">
            <KTIcon iconName={icon} className="fs-2" />
          </span>
        )}
        {fontIcon && app?.sidebar?.default?.menu?.iconType === 'font' && (
          <i
            className={clsx('bi fs-3', fontIcon)}
            style={{
              marginInlineEnd: '0.5rem',
            }}
          ></i>
        )}
        <span className="menu-title">{title}</span>
      </Link>
      {children}
    </div>
  );
};

export { SidebarMenuItem };
