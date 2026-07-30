/**
 * Literal copy of vendor/metronic/.../sidebar-menu/SidebarMenu.tsx
 * with mechanical class substitution.
 */
import { SidebarMenuMain } from '@/shared/components/Sidebar/SidebarMenuMain';

const SidebarMenu = () => {
  return (
    <div
      className={clsxJoin(
        /* Keep app-sidebar-menu for Metronic minimize CSS hooks */
        'app-sidebar-menu',
        /* overflow-hidden */
        'overflow-hidden',
        /* flex-column-fluid → grow in column flex parent */
        'flex flex-col grow',
      )}
    >
      <div
        id="kt_app_sidebar_menu_wrapper"
        className={clsxJoin(
          'app-sidebar-wrapper',
          /* hover-scroll-overlay-y → overflow-y auto (scroll paint still from Metronic if present) */
          'overflow-y-auto',
          /* my-5 → margin-block 1.25rem */
          'my-5',
        )}
        data-kt-scroll="true"
        data-kt-scroll-activate="true"
        data-kt-scroll-height="auto"
        data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
        data-kt-scroll-wrappers="#kt_app_sidebar_menu"
        data-kt-scroll-offset="5px"
        data-kt-scroll-save-state="true"
      >
        <div
          className={clsxJoin(
            /* menu menu-column menu-rounded menu-sub-indention → column flex + rounded links via children */
            'flex flex-col',
            /* px-3 → padding-inline 0.75rem */
            'px-3',
          )}
          id="#kt_app_sidebar_menu"
          data-kt-menu="true"
          data-kt-menu-expand="false"
        >
          <SidebarMenuMain />
        </div>
      </div>
    </div>
  );
};

/** Tiny local join to avoid importing cn while keeping literal single-string classNames readable. */
function clsxJoin(...parts: Array<string | false | null | undefined>): string {
  return parts.filter(Boolean).join(' ');
}

export { SidebarMenu };
