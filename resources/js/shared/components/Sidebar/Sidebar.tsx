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
 * Tailwind-native app sidebar.
 *
 * Visuals use design-system tokens (`bg-sidebar`, `text-sidebar-primary`, etc.)
 * so light/dark + per-app (`data-app`) colors work from day one.
 *
 * Layout geometry still cooperates with Metronic's shell via `id="kt_app_sidebar"`,
 * the `app-sidebar` class, and body `data-kt-app-sidebar-*` attrs — until header /
 * toolbar are replaced in a later pass. Menu markup itself is not Metronic.
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

    return () => {
      // Leave layout attrs in place; Metronic header may still rely on them during the transition.
    };
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
      className={cn(
        'app-sidebar ds-sidebar flex flex-col',
        'bg-sidebar text-sidebar-foreground border-e border-sidebar-border',
      )}
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
        className="app-sidebar-logo relative flex h-[70px] shrink-0 items-center px-6"
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
            'absolute top-1/2 z-10 flex size-8 -translate-y-1/2 items-center justify-center rounded-md border border-sidebar-border bg-sidebar shadow-sm',
            'text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
            'start-full -translate-x-1/2 rtl:translate-x-1/2',
            minimized && 'rotate-180',
          )}
        >
          <ChevronLeft className="size-4 rtl:rotate-180" />
        </button>
      </div>

      <div className="app-sidebar-menu flex min-h-0 flex-1 flex-col overflow-hidden">
        <nav
          id="kt_app_sidebar_menu_wrapper"
          className="app-sidebar-wrapper my-4 flex-1 overflow-y-auto px-3"
          aria-label="Main"
        >
          <div className="space-y-0.5 pb-6">
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
