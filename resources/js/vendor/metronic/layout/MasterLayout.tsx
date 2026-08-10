import { HeaderWrapper } from './components/header'
import { ScrollTop } from './components/scroll-top'
import { FooterWrapper } from './components/footer'
import { Sidebar } from './components/sidebar'
import { PageDataProvider } from './core'
import { ReactNode, useEffect } from "react";
import { Head, usePage } from "@inertiajs/react";
import { reInitMenu } from "@/vendor/metronic/helpers";
import ToastEffect from "@/shared/components/toaster/toast-effect";
import ToastContainer from "@/shared/components/toaster/toast-container";
import { toast } from 'sonner';
import { ADMIN_NOTIFICATION_RECEIVED_EVENT } from '@/apps/admin/layouts/header-menus/HeaderNotificationsMenu';
import { makeOffline, makeOnline } from '@/shared/helpers/general';

import './style.css';

type Props = {
  children: ReactNode
  head?: string
}

type AuthUserWithSocket = {
  socket_id?: string
}

export default function MasterLayout({ children, head }: Props) {
  const url = usePage().url
  const authUser = (usePage().props.auth?.user ?? null) as AuthUserWithSocket | null

  useEffect(() => {
    reInitMenu()
  }, [url])

  useEffect(() => {
    window.Echo.join('online')
      .here((users: { socket_id: string }[]) => {
        users.forEach((user) => {
          makeOnline(user)
        })
      })
      .joining((user: { socket_id: string }) => {
        makeOnline(user)
      })
      .leaving((user: { socket_id: string }) => {
        makeOffline(user)
      })

    return () => {
      window.Echo.leave('online')
    }
  }, [])

  useEffect(() => {
    const socketId = authUser?.socket_id
    if (!socketId) {
      return
    }

    window.Echo.private(socketId)
      .notification((notification: { id?: string; title: string; body: string }) => {
        toast.info(notification.title, {
          description: notification.body,
        })
        window.dispatchEvent(new CustomEvent(ADMIN_NOTIFICATION_RECEIVED_EVENT))
      })

    return () => {
      window.Echo.leave(socketId)
    }
  }, [authUser?.socket_id])

  return (
    <PageDataProvider>
      <ToastContainer />
      <ToastEffect />
      <Head title={head} />
      <div className='d-flex flex-column flex-root app-root' id='kt_app_root'>
        <div className='app-page flex-column flex-column-fluid' id='kt_app_page'>
          <HeaderWrapper />
          <div className='app-wrapper flex-column flex-row-fluid' id='kt_app_wrapper'>
            <Sidebar />
            <div className='app-main flex-column flex-row-fluid' id='kt_app_main'>
              <div className='d-flex flex-column flex-column-fluid'>
                {children}
              </div>
              <FooterWrapper />
            </div>
          </div>
        </div>
      </div>
      <ScrollTop />
    </PageDataProvider>
  )
}
