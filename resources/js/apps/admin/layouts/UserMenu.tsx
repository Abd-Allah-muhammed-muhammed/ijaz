import AuthController from '@/actions/App/Http/Controllers/Dashboard/AuthController';
import { Avatar, AvatarFallback, AvatarImage } from '@/shared/components/ui/avatar';
import { Button } from '@/shared/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
  DropdownMenuTrigger,
} from '@/shared/components/ui/dropdown-menu';
import { url } from '@/shared/helpers/general';
import { getSupportedLocales } from '@/shared/hooks/use-locales';
import type { Admin } from '@/shared/types/models';
import { Link, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

const locales = getSupportedLocales();

function replaceLocale(currentLocale: string, locale: string, path: string): string {
  if (path === `/${currentLocale}`) {
    return `/${locale}`;
  }

  return path.replace(`/${currentLocale}/`, `/${locale}/`);
}

export function UserMenu() {
  const logoutForm = useForm();
  const page = usePage();
  const currentUser = (page.props.auth?.user ?? null) as Admin | null;
  const currentLocale = (page.props as { app?: { locale?: string } }).app?.locale ?? 'en';
  const { t } = useTranslation();

  if (!currentUser) {
    return null;
  }

  const roles = currentUser.roles ?? [];
  const roleLabel = currentUser.root ? 'root' : roles.length > 0 ? roles[0].name : 'user';
  const initials = currentUser.name
    .split(/\s+/)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          className="h-9 gap-2 rounded-full px-1.5 pe-2 text-foreground hover:bg-accent"
        >
          <Avatar className="size-8">
            <AvatarImage src={currentUser.image} alt={currentUser.name} />
            <AvatarFallback className="bg-primary/10 text-xs font-medium text-primary">
              {initials}
            </AvatarFallback>
          </Avatar>
          <span className="hidden max-w-[8rem] truncate text-sm font-medium md:inline">
            {currentUser.name}
          </span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-64">
        <DropdownMenuLabel className="font-normal">
          <div className="flex flex-col gap-1">
            <p className="text-sm font-medium leading-none">{currentUser.name}</p>
            <p className="text-xs leading-none text-muted-foreground">{currentUser.email}</p>
            <p className="pt-1 text-[0.7rem] font-medium uppercase tracking-wide text-muted-foreground">
              {roleLabel}
            </p>
          </div>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuItem asChild>
          <Link href={AuthController.profile().url}>{t('my_profile', { defaultValue: 'My Profile' })}</Link>
        </DropdownMenuItem>
        <DropdownMenuSub>
          <DropdownMenuSubTrigger>
            {t('language', { defaultValue: 'Language' })}
          </DropdownMenuSubTrigger>
          <DropdownMenuSubContent className="w-44">
            {Object.entries(locales).map(([locale, meta]) => (
              <DropdownMenuItem
                key={locale}
                onClick={() => {
                  window.location.href = url(replaceLocale(currentLocale, locale, page.url));
                }}
                className={locale === currentLocale ? 'bg-accent' : undefined}
              >
                <img
                  className="size-4 rounded-sm object-cover"
                  src={url(meta.flag)}
                  alt={meta.native}
                />
                {meta.native}
              </DropdownMenuItem>
            ))}
          </DropdownMenuSubContent>
        </DropdownMenuSub>
        <DropdownMenuSeparator />
        <DropdownMenuItem
          onClick={() => {
            logoutForm.submit(AuthController.logout());
          }}
          className="text-destructive focus:text-destructive"
        >
          {t('sign_out', { defaultValue: 'Sign Out' })}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
