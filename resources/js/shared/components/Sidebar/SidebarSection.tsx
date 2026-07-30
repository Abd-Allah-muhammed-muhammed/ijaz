import { SidebarNavItem } from './SidebarNavItem';
import type { SidebarNavSection } from './types';

type Props = {
  section: SidebarNavSection;
};

/**
 * Section header matches Metronic markup:
 * `menu-content pt-8 pb-2` + `menu-section text-muted text-uppercase fs-8 ls-1`
 * - pt-8 = 2rem, pb-2 = 0.5rem, horizontal = `$menu-link-padding-x` 1rem
 * - fs-8 = 0.85rem, ls-1 = 0.1rem
 * - text-muted = `$gray-500` #99A1B7 (class used in original SidebarMenuMain)
 */
export function SidebarSection({ section }: Props) {
  if (section.show === false) {
    return null;
  }

  const visibleItems = section.items.filter((item) => item.show !== false);

  if (visibleItems.length === 0 && !section.title) {
    return null;
  }

  if (section.title && visibleItems.length === 0) {
    return null;
  }

  return (
    <div className="ds-sidebar-section">
      {section.title ? (
        <div className="ds-sidebar-section-title px-4 pb-2 pt-8 text-[0.85rem] font-normal uppercase tracking-[0.1rem] text-[#99A1B7]">
          {section.title}
        </div>
      ) : null}
      {visibleItems.map((item) => (
        <SidebarNavItem
          key={item.href + item.title}
          href={item.href}
          title={item.title}
          icon={item.icon}
          isActive={item.isActive}
          show={item.show}
        />
      ))}
    </div>
  );
}
