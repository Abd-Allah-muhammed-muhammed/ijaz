import { HeaderWrapper } from '@/vendor/metronic/layout/components/header';
import { ScrollTop } from '@/vendor/metronic/layout/components/scroll-top';
import { FooterWrapper } from '@/vendor/metronic/layout/components/footer';
import { PageDataProvider } from '@/vendor/metronic/layout/core';
import { Sidebar } from '@/shared/components/Sidebar';
import { useAdminSidebarSections } from '@/apps/admin/layouts/use-admin-sidebar-nav';
import { ReactNode, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { reInitMenu } from '@/vendor/metronic/helpers';
import ToastEffect from '@/shared/components/toaster/toast-effect';
import ToastContainer from '@/shared/components/toaster/toast-container';

import '@/vendor/metronic/layout/style.css';

type Props = {
  children: ReactNode;
  head?: string;
};

/**
 * Admin shell layout — Metronic header/toolbar/content retained for now;
 * sidebar is the Tailwind-native design-system component.
 */
export default function MasterLayout({ children, head }: Props) {
  const url = usePage().url;
  const sections = useAdminSidebarSections();

  useEffect(() => {
    reInitMenu();
  }, [url]);

  useEffect(() => {
    window.Echo.join('online');
    return () => {
      window.Echo.disconnect();
    };
  }, []);

  return (
    <PageDataProvider>
      <ToastContainer />
      <ToastEffect />
      <Head title={head} />
      <div className="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div className="app-page flex-column flex-column-fluid" id="kt_app_page">
          <HeaderWrapper />
          <div className="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <Sidebar sections={sections} />
            <div className="app-main flex-column flex-row-fluid" id="kt_app_main">
              <div className="d-flex flex-column flex-column-fluid">{children}</div>
              <FooterWrapper />
            </div>
          </div>
        </div>
      </div>
      <ScrollTop />
    </PageDataProvider>
  );
}
