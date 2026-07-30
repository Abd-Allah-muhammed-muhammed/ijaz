export type SidebarNavItem = {
  title: string;
  href: string;
  icon?: string;
  isActive?: boolean;
  show?: boolean;
};

export type SidebarNavSection = {
  /** Optional section heading (uppercase label). Omit for standalone items like Dashboard. */
  title?: string;
  /** When false, the heading and all items are hidden. Defaults to true. */
  show?: boolean;
  items: SidebarNavItem[];
};

export type SidebarProps = {
  sections: SidebarNavSection[];
  /** Logo / brand link target. Defaults to `/dashboard`. */
  homeHref?: string;
  logoSrc?: string;
};
