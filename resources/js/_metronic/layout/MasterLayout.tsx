import { HeaderWrapper } from './components/header'
import { ScrollTop } from './components/scroll-top'
import { FooterWrapper } from './components/footer'
import { Sidebar } from './components/sidebar'
import { PageDataProvider } from './core'
import { ReactNode, useEffect } from "react";
import { Head, usePage } from "@inertiajs/react";
import { reInitMenu } from "@/_metronic/helpers";
import ToastEffect from "@/components/toaster/toast-effect";
import ToastContainer from "@/components/toaster/toast-container";

import './style.css';

type Props = {
  children: ReactNode
  head?: string
}


export default function MasterLayout({ children, head }: Props) {

  const url = usePage().url
  useEffect(() => {
    reInitMenu()
  }, [url])

  useEffect(() => {
    window.Echo.join('online')
    return () => {
      window.Echo.disconnect();
    }
  }, []);

  return (
    <PageDataProvider>
      <ToastContainer />
      <ToastEffect />
      {/* <I18nextEffect children={undefined} /> */}
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

      {/* begin:: Drawers */}
      {/*<ActivityDrawer/>*/}
      {/*<DrawerMessenger/>*/}
      {/* end:: Drawers */}

      {/* begin:: Modals */}
      {/*<InviteUsers/>*/}
      {/*<UpgradePlan/>*/}
      {/* end:: Modals */}
      <ScrollTop />

    </PageDataProvider>
  )
}

