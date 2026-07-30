import { Header } from '@/apps/admin/layouts/Header';
import { AdminShellProvider, useAdminShell } from '@/apps/admin/layouts/shell-context';
import { Sidebar } from '@/shared/components/Sidebar';
import ToastContainer from '@/shared/components/toaster/toast-container';
import ToastEffect from '@/shared/components/toaster/toast-effect';
import { cn } from '@/shared/lib/utils';
import { PageDataProvider } from '@/vendor/metronic/layout/core';
import { Head } from '@inertiajs/react';
import { type ReactNode, useEffect } from 'react';

export type AdminLayoutProps = {
  children: ReactNode;
  head?: string;
};

function AdminShellFrame({ children, head }: AdminLayoutProps) {
  const { collapsed } = useAdminShell();

  useEffect(() => {
    window.Echo.join('online');

    return () => {
      window.Echo.disconnect();
    };
  }, []);

  return (
    <>
      <ToastContainer />
      <ToastEffect />
      <Head title={head} />
      <div className="min-h-svh bg-background text-foreground">
        <Sidebar />
        <div
          className={cn(
            'flex min-h-svh flex-col transition-[padding] duration-200 ease-out',
            'ps-0',
            collapsed
              ? 'lg:ps-[var(--admin-shell-sidebar-collapsed-width)]'
              : 'lg:ps-[var(--admin-shell-sidebar-width)]',
          )}
        >
          <Header />
          <main className="flex min-h-0 flex-1 flex-col">{children}</main>
        </div>
      </div>
    </>
  );
}

/**
 * Original Tailwind + shadcn Admin shell.
 * Drop-in replacement for Metronic MasterLayout — same PageTitle / Toolbar / Content contract.
 */
export default function AdminLayout({ children, head }: AdminLayoutProps) {
  return (
    <PageDataProvider>
      <AdminShellProvider>
        <AdminShellFrame head={head}>{children}</AdminShellFrame>
      </AdminShellProvider>
    </PageDataProvider>
  );
}
