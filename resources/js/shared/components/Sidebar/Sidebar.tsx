import { Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { url as assetUrl } from '@/shared/helpers/general';
import { cn } from '@/shared/lib/utils';
import { SidebarSection } from './SidebarSection';
import type { SidebarProps } from './types';

const SIDEBAR_MINIMIZE_ATTR = 'data-kt-app-sidebar-minimize';
const SIDEBAR_HOVERABLE_ATTR = 'data-kt-app-sidebar-hoverable';

/**
 * Tailwind-native sidebar painted to match Metronic `dark-sidebar` source values
 * (`$coal-500` #0D0E12 bg — fixed, independent of page light/dark).
 *
 * Active item is the only intentional deviation: uses app `--primary` instead of
 * Metronic `$app-sidebar-dark-menu-link-bg-color-active` (#1C1C21).
 *
 * Geometry IDs / `app-sidebar` class retained for Metronic header/content shell.
 */
export function Sidebar({
  sections,
  homeHref = '/dashboard',
  logoSrc = 'logo2.png',
}: SidebarProps) {
  const [minimized, setMinimized] = useState(false);

  useEffect(() => {
    const body = document.body;
    body.setAttribute('data-kt-app-sidebar-enabled', 'true');
    body.setAttribute('data-kt-app-sidebar-fixed', 'true');
    body.setAttribute('data-kt-app-sidebar-push-header', 'true');
    body.setAttribute('data-kt-app-sidebar-push-toolbar', 'true');
    body.setAttribute('data-kt-app-sidebar-push-footer', 'true');
    body.setAttribute(SIDEBAR_HOVERABLE_ATTR, 'true');

    const initial = body.getAttribute(SIDEBAR_MINIMIZE_ATTR) === 'on';
    setMinimized(initial);
  }, []);

  const toggleMinimize = useCallback(() => {
    setMinimized((prev) => {
      const next = !prev;
      if (next) {
        document.body.setAttribute(SIDEBAR_MINIMIZE_ATTR, 'on');
      } else {
        document.body.removeAttribute(SIDEBAR_MINIMIZE_ATTR);
      }
      return next;
    });
  }, []);

  return (
    <aside
      id="kt_app_sidebar"
      className="app-sidebar ds-sidebar flex flex-col"
      data-kt-drawer="true"
      data-kt-drawer-name="app-sidebar"
      data-kt-drawer-activate="{default: true, lg: false}"
      data-kt-drawer-overlay="true"
      data-kt-drawer-width="225px"
      data-kt-drawer-direction="start"
      data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle"
    >
      <div
        id="kt_app_sidebar_logo"
        className="app-sidebar-logo relative flex h-[70px] shrink-0 items-center justify-between px-6"
      >
        <Link href={homeHref} className="flex items-center">
          <img
            alt="Logo"
            src={assetUrl(logoSrc)}
            className="app-sidebar-logo-default h-[25px]"
          />
          <img
            alt="Logo"
            src={assetUrl(logoSrc)}
            className="app-sidebar-logo-minimize h-5"
          />
        </Link>

        <button
          type="button"
          id="kt_app_sidebar_toggle"
          aria-label={minimized ? 'Expand sidebar' : 'Collapse sidebar'}
          aria-pressed={minimized}
          onClick={toggleMinimize}
          className={cn(
            /* Metronic `.app-sidebar-toggle`: body-bg, border #F1F1F2, soft shadow */
            'app-sidebar-toggle absolute top-1/2 z-10 flex size-[30px] -translate-y-1/2 items-center justify-center',
            'rounded-md border border-[#F1F1F2] bg-white text-[#99A1B7] shadow-[0px_8px_14px_rgba(15,42,81,0.04)]',
            'transition-transform duration-200 hover:text-primary',
            'start-full -translate-x-1/2 rtl:translate-x-1/2',
            minimized && 'rotate-180',
          )}
        >
          <ChevronLeft className="size-3.5 rtl:rotate-180" />
        </button>
      </div>

      <div className="app-sidebar-menu flex min-h-0 flex-1 flex-col overflow-hidden">
        {/* Metronic: `my-5` wrapper + `menu … px-3` */}
        <nav
          id="kt_app_sidebar_menu_wrapper"
          className="app-sidebar-wrapper my-5 flex-1 overflow-y-auto px-3"
          aria-label="Main"
        >
          <div className="pb-6">
            {sections.map((section, index) => (
              <SidebarSection
                key={section.title ?? `section-${index}`}
                section={section}
              />
            ))}
          </div>
        </nav>
      </div>
    </aside>
  );
}
