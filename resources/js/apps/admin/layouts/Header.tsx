import { useAdminShell } from '@/apps/admin/layouts/shell-context';
import { ThemeToggle } from '@/apps/admin/layouts/ThemeToggle';
import { UserMenu } from '@/apps/admin/layouts/UserMenu';
import { Button } from '@/shared/components/ui/button';
import { KTIcon } from '@/vendor/metronic/helpers';
import { useTranslation } from 'react-i18next';

export function Header() {
  const { setMobileOpen, toggleCollapsed } = useAdminShell();
  const { t } = useTranslation();

  return (
    <header className="sticky top-0 z-30 flex h-[var(--admin-shell-header-height)] shrink-0 items-center gap-3 border-b border-border bg-background/90 px-4 backdrop-blur-md md:px-6">
      <Button
        type="button"
        variant="ghost"
        size="icon"
        className="size-9 text-muted-foreground lg:hidden"
        aria-label={t('open_menu', { defaultValue: 'Open menu' })}
        onClick={() => setMobileOpen(true)}
      >
        <KTIcon iconName="abstract-14" className="text-[1.25rem]! leading-none" />
      </Button>

      <Button
        type="button"
        variant="ghost"
        size="icon"
        className="hidden size-9 text-muted-foreground lg:inline-flex"
        aria-label={t('toggle_sidebar', { defaultValue: 'Toggle sidebar' })}
        onClick={toggleCollapsed}
      >
        <KTIcon iconName="black-left-line" className="text-[1.2rem]! leading-none rtl:rotate-180" />
      </Button>

      <div className="ms-auto flex items-center gap-1">
        <ThemeToggle />
        <UserMenu />
      </div>
    </header>
  );
}
