import { Button } from '@/shared/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/shared/components/ui/dropdown-menu';
import { KTIcon } from '@/vendor/metronic/helpers';
import {
  type ThemeModeType,
  useThemeMode,
} from '@/vendor/metronic/partials/layout/theme-mode/ThemeModeProvider';
import { ThemeModeComponent } from '@/vendor/metronic/assets/ts/layout';
import { cn } from '@/shared/lib/utils';
import { useTranslation } from 'react-i18next';

const systemMode = ThemeModeComponent.getSystemMode() as 'light' | 'dark';

export function ThemeToggle() {
  const { mode, menuMode, updateMode, updateMenuMode } = useThemeMode();
  const { t } = useTranslation();
  const resolved = mode === 'system' ? systemMode : mode;

  const switchMode = (next: ThemeModeType) => {
    updateMenuMode(next);
    updateMode(next);
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          size="icon"
          className="size-9 text-muted-foreground hover:text-foreground"
          aria-label={t('theme', { defaultValue: 'Theme' })}
        >
          <KTIcon
            iconName={resolved === 'dark' ? 'moon' : 'night-day'}
            className="text-[1.25rem]! leading-none"
          />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-40">
        {(
          [
            { value: 'light', icon: 'night-day', label: t('light', { defaultValue: 'Light' }) },
            { value: 'dark', icon: 'moon', label: t('dark', { defaultValue: 'Dark' }) },
            { value: 'system', icon: 'screen', label: t('system', { defaultValue: 'System' }) },
          ] as const
        ).map((item) => (
          <DropdownMenuItem
            key={item.value}
            onClick={() => switchMode(item.value)}
            className={cn(menuMode === item.value && 'bg-accent')}
          >
            <KTIcon iconName={item.icon} className="text-[1.1rem]! leading-none" />
            {item.label}
          </DropdownMenuItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
