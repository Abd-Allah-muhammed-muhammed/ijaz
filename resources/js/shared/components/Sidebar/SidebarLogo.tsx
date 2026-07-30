import { useAdminShell } from '@/apps/admin/layouts/shell-context';
import { Button } from '@/shared/components/ui/button';
import { url } from '@/shared/helpers/general';
import { cn } from '@/shared/lib/utils';
import { Link } from '@inertiajs/react';
import { KTIcon } from '@/vendor/metronic/helpers';
import { useTranslation } from 'react-i18next';

export function SidebarLogo() {
  const { collapsed, toggleCollapsed, setMobileOpen } = useAdminShell();
  const { t } = useTranslation();

  return (
    <div
      className={cn(
        'flex h-[var(--admin-shell-header-height)] shrink-0 items-center gap-2 border-b border-[var(--admin-shell-sidebar-border)] px-3',
        collapsed ? 'justify-center' : 'justify-between px-4',
      )}
    >
      <Link
        href="/dashboard"
        className="flex min-w-0 items-center gap-2 no-underline"
        onClick={() => setMobileOpen(false)}
      >
        <img
          alt="Ijaz"
          src={url('logo2.png')}
          className={cn('h-7 w-auto object-contain', collapsed && 'h-6')}
        />
        {!collapsed && (
          <span className="truncate text-sm font-semibold tracking-tight text-[var(--admin-shell-sidebar-foreground)]">
            Ijaz
          </span>
        )}
      </Link>

      {!collapsed && (
        <Button
          type="button"
          variant="ghost"
          size="icon"
          className="hidden size-8 text-[var(--admin-shell-sidebar-muted)] hover:bg-[var(--admin-shell-sidebar-hover)] hover:text-[var(--admin-shell-sidebar-foreground)] lg:inline-flex"
          aria-label={t('collapse_sidebar', { defaultValue: 'Collapse sidebar' })}
          onClick={toggleCollapsed}
        >
          <KTIcon iconName="black-left-line" className="text-[1.1rem]! leading-none rtl:rotate-180" />
        </Button>
      )}
    </div>
  );
}
