import { useAdminShell } from '@/apps/admin/layouts/shell-context';
import { SidebarLogo } from '@/shared/components/Sidebar/SidebarLogo';
import { SidebarMenu } from '@/shared/components/Sidebar/SidebarMenu';
import { cn } from '@/shared/lib/utils';

/**
 * Original Admin sidebar — always-dark chrome, SaaS-inspired.
 * Collapse/expand and mobile drawer are owned by AdminShellProvider.
 */
export function Sidebar() {
  const { collapsed, mobileOpen, setMobileOpen } = useAdminShell();

  return (
    <>
      {/* Mobile backdrop */}
      <div
        role="presentation"
        className={cn(
          'fixed inset-0 z-40 bg-black/50 transition-opacity duration-200 lg:hidden',
          mobileOpen ? 'opacity-100' : 'pointer-events-none opacity-0',
        )}
        onClick={() => setMobileOpen(false)}
      />

      <aside
        className={cn(
          'fixed inset-y-0 start-0 z-50 flex flex-col',
          'bg-[var(--admin-shell-sidebar)] text-[var(--admin-shell-sidebar-foreground)]',
          'border-e border-[var(--admin-shell-sidebar-border)]',
          'transition-[width,transform] duration-200 ease-out',
          'w-[var(--admin-shell-sidebar-width)]',
          collapsed && 'lg:w-[var(--admin-shell-sidebar-collapsed-width)]',
          mobileOpen ? 'translate-x-0' : '-translate-x-full rtl:translate-x-full',
          'lg:translate-x-0 rtl:lg:translate-x-0',
        )}
      >
        <SidebarLogo />
        <SidebarMenu />
      </aside>
    </>
  );
}
