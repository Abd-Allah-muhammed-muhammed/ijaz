import { SidebarNavItem } from './SidebarNavItem';
import type { SidebarNavSection } from './types';

type Props = {
  section: SidebarNavSection;
};

export function SidebarSection({ section }: Props) {
  if (section.show === false) {
    return null;
  }

  const visibleItems = section.items.filter((item) => item.show !== false);

  if (visibleItems.length === 0 && !section.title) {
    return null;
  }

  // Section with a title but no visible items (permission-gated) — hide entirely
  if (section.title && visibleItems.length === 0) {
    return null;
  }

  return (
    <div className="ds-sidebar-section space-y-0.5">
      {section.title ? (
        <div className="ds-sidebar-section-title px-3 pb-1.5 pt-6 text-[0.65rem] font-semibold uppercase tracking-wider text-muted-foreground">
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
