import { useAdminShell } from '@/apps/admin/layouts/shell-context';
import { cn } from '@/shared/lib/utils';
import type { ReactNode } from 'react';

type SidebarSectionProps = {
  label: string;
  children?: ReactNode;
};

/**
 * Muted uppercase section label. Hidden when the sidebar is collapsed.
 */
export function SidebarSection({ label }: SidebarSectionProps) {
  const { collapsed } = useAdminShell();

  if (collapsed) {
    return <div className="my-2 mx-3 h-px bg-[var(--admin-shell-sidebar-border)]" aria-hidden />;
  }

  return (
    <div className="px-5 pb-1.5 pt-6 first:pt-2">
      <span
        className={cn(
          'text-[0.65rem] font-semibold uppercase tracking-[0.12em]',
          'text-[var(--admin-shell-sidebar-muted)]',
        )}
      >
        {label}
      </span>
    </div>
  );
}
