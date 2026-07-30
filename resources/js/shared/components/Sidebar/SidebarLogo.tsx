/**
 * Literal copy of vendor/metronic/.../sidebar/SidebarLogo.tsx
 * with mechanical class substitution.
 */
import { Link } from '@inertiajs/react';
import clsx from 'clsx';
import { KTIcon } from '@/vendor/metronic/helpers';
import { useLayout } from '@/vendor/metronic/layout/core';
import { type MutableRefObject, useEffect, useRef } from 'react';
import { ToggleComponent } from '@/vendor/metronic/assets/ts/components';
import { url } from '@/shared/helpers/general';

type PropsType = {
  sidebarRef: MutableRefObject<HTMLDivElement | null>;
};

const SidebarLogo = (props: PropsType) => {
  const { config } = useLayout();
  const toggleRef = useRef<HTMLDivElement>(null);

  const appSidebarDefaultMinimizeDesktopEnabled =
    config?.app?.sidebar?.default?.minimize?.desktop?.enabled;
  const appSidebarDefaultCollapseDesktopEnabled =
    config?.app?.sidebar?.default?.collapse?.desktop?.enabled;
  const toggleType = appSidebarDefaultCollapseDesktopEnabled
    ? 'collapse'
    : appSidebarDefaultMinimizeDesktopEnabled
      ? 'minimize'
      : '';
  const toggleState = appSidebarDefaultMinimizeDesktopEnabled ? 'active' : '';
  const appSidebarDefaultMinimizeDefault = config.app?.sidebar?.default?.minimize?.desktop?.default;

  useEffect(() => {
    setTimeout(() => {
      const toggleObj = ToggleComponent.getInstance(toggleRef.current!) as ToggleComponent | null;

      if (toggleObj === null) {
        return;
      }

      toggleObj.on('kt.toggle.change', function () {
        props.sidebarRef.current!.classList.add('animating');
        setTimeout(function () {
          props.sidebarRef.current!.classList.remove('animating');
        }, 300);
      });
    }, 600);
  }, [toggleRef, props.sidebarRef]);

  return (
    <div
      className={clsx(
        /* Keep app-sidebar-logo for Metronic height/minimize logo swap CSS */
        'app-sidebar-logo',
        /* px-6 → padding-inline 1.5rem */
        'px-6',
        /* dark-sidebar logo border: 1px dashed $light-light-dark #1F212A */
        'border-b border-dashed border-[#1F212A]',
      )}
      id="kt_app_sidebar_logo"
    >
      <Link href="/dashboard">
        {config.layoutType === 'dark-sidebar' ? (
          <img
            alt="Logo"
            src={url('logo2.png')}
            className="app-sidebar-logo-default h-[25px]"
          />
        ) : (
          <>
            <img
              alt="Logo"
              src={url('logo2.png')}
              className="app-sidebar-logo-default theme-light-show h-[25px]"
            />
            <img
              alt="Logo"
              src={url('logo2.png')}
              className="app-sidebar-logo-default theme-dark-show h-[25px]"
            />
          </>
        )}

        <img
          alt="Logo"
          src={url('logo2.png')}
          className="app-sidebar-logo-minimize h-[20px]"
        />
      </Link>

      {(appSidebarDefaultMinimizeDesktopEnabled || appSidebarDefaultCollapseDesktopEnabled) && (
        <div
          ref={toggleRef}
          id="kt_app_sidebar_toggle"
          className={clsx(
            /* Keep app-sidebar-toggle for Metronic desktop toggle CSS */
            'app-sidebar-toggle',
            /* btn btn-icon btn-sm btn-shadow btn-color-muted → compact icon button */
            'flex items-center justify-center',
            'h-[30px] w-[30px]',
            'rounded-[0.475rem]',
            'border border-[#F1F1F2]',
            'bg-white',
            'text-[#99A1B7]',
            'shadow-[0px_8px_14px_rgba(15,42,81,0.04)]',
            'cursor-pointer',
            /* position-absolute top-50 start-100 translate-middle */
            'absolute top-1/2 start-full -translate-x-1/2 -translate-y-1/2',
            /* rotate + active handled by Metronic toggle CSS */
            'rotate',
            { active: appSidebarDefaultMinimizeDefault },
          )}
          data-kt-toggle="true"
          data-kt-toggle-state={toggleState}
          data-kt-toggle-target="body"
          data-kt-toggle-name={`app-sidebar-${toggleType}`}
        >
          <KTIcon iconName="black-left-line" className="text-[1.35rem]! rotate-180 ms-1" />
        </div>
      )}
    </div>
  );
};

export { SidebarLogo };
