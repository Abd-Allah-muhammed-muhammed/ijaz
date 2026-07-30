/* eslint-disable no-prototype-builtins */
/**
 * Literal copy of vendor/metronic/layout/components/sidebar/Sidebar.tsx
 * with mechanical class substitution (see commit message / mapping table).
 */
import clsx from 'clsx';
import { useEffect, useRef } from 'react';
import { type ILayout, useLayout } from '@/vendor/metronic/layout/core';
import { SidebarMenu } from '@/shared/components/Sidebar/SidebarMenu';
import { SidebarLogo } from '@/shared/components/Sidebar/SidebarLogo';

const Sidebar = () => {
  const { config } = useLayout();
  const sidebarRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    updateDOM(config);
  }, [config]);

  if (!config.app?.sidebar?.display) {
    return null;
  }

  return (
    <>
      {(config.layoutType === 'dark-sidebar' || config.layoutType === 'light-sidebar') && (
        <div
          ref={sidebarRef}
          id="kt_app_sidebar"
          className={clsx(
            /* Keep `app-sidebar` for Metronic layout geometry (width/fixed/minimize). */
            'app-sidebar',
            /* dark-sidebar bg $coal-500 → #0D0E12 (fixed paint) */
            'bg-[#0D0E12]!',
            /* config.app.sidebar.default.class was `flex-column` → flex flex-col */
            config.app?.sidebar?.default?.class === 'flex-column'
              ? 'flex flex-col'
              : config.app?.sidebar?.default?.class,
          )}
        >
          <SidebarLogo sidebarRef={sidebarRef} />
          <SidebarMenu />
        </div>
      )}
    </>
  );
};

const updateDOM = (config: ILayout) => {
  if (config.layoutType === 'dark-sidebar' || config.layoutType === 'light-sidebar') {
    if (config.app?.sidebar?.default?.minimize?.desktop?.enabled) {
      if (config.app?.sidebar?.default?.minimize?.desktop?.default) {
        document.body.setAttribute('data-kt-app-sidebar-minimize', 'on');
      }

      if (config.app?.sidebar?.default?.minimize?.desktop?.hoverable) {
        document.body.setAttribute('data-kt-app-sidebar-hoverable', 'true');
      }
    }

    if (config.app?.sidebar?.default?.minimize?.mobile?.enabled) {
      if (config.app?.sidebar?.default?.minimize?.mobile?.default) {
        document.body.setAttribute('data-kt-app-sidebar-minimize-mobile', 'on');
      }

      if (config.app?.sidebar?.default?.minimize?.mobile?.hoverable) {
        document.body.setAttribute('data-kt-app-sidebar-hoverable-mobile', 'true');
      }
    }

    if (config.app?.sidebar?.default?.collapse?.desktop?.enabled) {
      if (config.app?.sidebar?.default?.collapse?.desktop?.default) {
        document.body.setAttribute('data-kt-app-sidebar-collapse', 'on');
      }
    }

    if (config.app?.sidebar?.default?.collapse?.mobile?.enabled) {
      if (config.app?.sidebar?.default?.collapse?.mobile?.default) {
        document.body.setAttribute('data-kt-app-sidebar-collapse-mobile', 'on');
      }
    }

    if (config.app?.sidebar?.default?.push) {
      if (config.app?.sidebar?.default?.push?.header) {
        document.body.setAttribute('data-kt-app-sidebar-push-header', 'true');
      }

      if (config.app?.sidebar?.default?.push?.toolbar) {
        document.body.setAttribute('data-kt-app-sidebar-push-toolbar', 'true');
      }

      if (config.app?.sidebar?.default?.push?.footer) {
        document.body.setAttribute('data-kt-app-sidebar-push-footer', 'true');
      }
    }

    if (config.app?.sidebar?.default?.stacked) {
      document.body.setAttribute('app-sidebar-stacked', 'true');
    }

    document.body.setAttribute('data-kt-app-sidebar-enabled', 'true');
    document.body.setAttribute(
      'data-kt-app-sidebar-fixed',
      config.app?.sidebar?.default?.fixed?.desktop?.toString() || '',
    );

    const appSidebarDefaultDrawerEnabled = config.app?.sidebar?.default?.drawer?.enabled;
    let appSidebarDefaultDrawerAttributes: { [attrName: string]: string } = {};
    if (appSidebarDefaultDrawerEnabled) {
      appSidebarDefaultDrawerAttributes = config.app?.sidebar?.default?.drawer?.attributes as {
        [attrName: string]: string;
      };
    }

    const appSidebarDefaultStickyEnabled = config.app?.sidebar?.default?.sticky?.enabled;
    let appSidebarDefaultStickyAttributes: { [attrName: string]: string } = {};
    if (appSidebarDefaultStickyEnabled) {
      appSidebarDefaultStickyAttributes = config.app?.sidebar?.default?.sticky?.attributes as {
        [attrName: string]: string;
      };
    }

    setTimeout(() => {
      const sidebarElement = document.getElementById('kt_app_sidebar');
      if (sidebarElement) {
        const sidebarAttributes = sidebarElement
          .getAttributeNames()
          .filter((t) => t.indexOf('data-') > -1);
        sidebarAttributes.forEach((attr) => sidebarElement.removeAttribute(attr));

        if (appSidebarDefaultDrawerEnabled) {
          for (const key in appSidebarDefaultDrawerAttributes) {
            if (appSidebarDefaultDrawerAttributes.hasOwnProperty(key)) {
              sidebarElement.setAttribute(key, appSidebarDefaultDrawerAttributes[key]);
            }
          }
        }

        if (appSidebarDefaultStickyEnabled) {
          for (const key in appSidebarDefaultStickyAttributes) {
            if (appSidebarDefaultStickyAttributes.hasOwnProperty(key)) {
              sidebarElement.setAttribute(key, appSidebarDefaultStickyAttributes[key]);
            }
          }
        }
      }
    }, 0);
  }
};

export { Sidebar };
