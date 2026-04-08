import {toast} from 'sonner'
import {HeaderWrapper} from '@/layouts/provider/header'
import {ScrollTop} from '@/layouts/provider/scroll-top'
import {FooterWrapper} from '@/layouts/provider/footer'
import {Sidebar} from '@/layouts/provider/sidebar'
import {PageDataProvider} from '@/layouts/provider/core'
import {ReactNode, useEffect, useState} from "react";
import {Head, router, usePage} from "@inertiajs/react";
import {KTIcon, reInitMenu} from "@/_metronic/helpers";
import ToastEffect from "@/components/toaster/toast-effect";
import ToastContainer from "@/components/toaster/toast-container";
import {Conversation, Order, Provider} from "@/types/models";
import {makeOffline, makeOnline} from "@/helpers/general";
import {useRecommendedOrdersContext} from "@/store/recommend-orders-context";
import {useTranslation} from "react-i18next";
import {useConversations} from "@/store/use-chat";
import {ChatEventEnum} from "@/Enums/Chat";
import ChatController from "@/actions/App/Http/Controllers/Provider/ChatController";
import {Button} from "react-bootstrap";

type Props = {
  children: ReactNode
  head?: string
}


export default function ProviderLayout({children, head}: Props) {
  const user = usePage().props.auth.user as unknown as Provider
  const [categories, setCategories] = useState<Set<number>>(new Set)
  const [oldCategories, setOldCategories] = useState<Set<number>>(new Set)
  const userCategories = user.categories || []
  const url = usePage().url
  const locale = usePage().props.app.locale
  const {setOrders} = useRecommendedOrdersContext();
  const {updateConversationForNewMessages, setCurrentSocketId, currentConversation} = useConversations();
  const {t} = useTranslation();

  useEffect(() => {
    const newCategories = new Set(userCategories.map((category) => category.id as number))
    if (newCategories.size == 0) {
      setCategories(new Set());
      setOldCategories(categories);
    } else if (newCategories.difference(categories).size) {
      setOldCategories(categories)
      setCategories(newCategories);
    }
  }, [userCategories]);


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
      });
    setCurrentSocketId(user.socket_id as string)

    return () => {
      window.Echo.leave('online');
    }
  }, []);

  useEffect(() => {
    window.Echo.private(user.socket_id)
      .notification((notification: { title: string, body: string }) => {
        toast.info(notification.title, {
          description: notification.body
        })
      })
      .listen(`.${ChatEventEnum.Chat_Updated}`, (chat: Conversation) => {
        const cleanUrl = url.split('?')[0];
        if (cleanUrl === `/${locale}${ChatController.index().url}`) {
          if (chat.last_message?.sender?.socket_id === user.socket_id) {
            chat.unread_count = 0;
          }
          updateConversationForNewMessages(chat);
          return
        }
        console.log('currentConversation = ', currentConversation)
        if (chat.last_message?.sender?.socket_id !== user.socket_id && chat.id !== currentConversation?.id) {
          toast.message(chat.last_message?.sender?.name, {
            description: chat.last_message?.content,
            id: chat.id,
            action: <Button
              variant='outline-secondary'
              className="ms-auto"
              title={t('view')}
              size='sm'
              onClick={() => {
                router.visit(ChatController.index().url, {
                  preserveScroll: true,
                  replace: true,
                  data: {conversation: chat.id}
                })
              }}
            >
              <KTIcon iconName="eye" className="fs-2"/>
            </Button>,
            duration: 5000,
            icon: <img src={chat.last_message?.sender?.image} className="rounded-circle"
                       style={{width: '40px', height: '40px'}} alt={'avatar'}/>,
            className: 'justify-content-start',
            classNames: {
              description: 'mb-0 flex-grow-1 text-start',
              icon: 'flex-shrink-0 rounded-circle w-40px h-40px',
            }
          })
        }

      })
    ;
    return () => {
      window.Echo.leave(user.socket_id);
    }
  }, []);

  useEffect(() => {
    if (categories.size === 0 && oldCategories.size === 0) {
      return;
    }
    oldCategories.difference(categories).forEach(id => {
      window.Echo.leave(`category.${id}`);
    })
    categories.difference(oldCategories).forEach((category) => {
      window.Echo.private(`category.${category}`)
        .listen('.new-order', (order: Order) => {
          console.log('new order')
          toast.warning(t('you have a new order in category') + ` ${order.category?.title}`,)
          setOrders((prevOrders) => {
            return [
              order,
              ...prevOrders
            ]
          })
        });
    });
  }, [categories]);


  return (
    <PageDataProvider>
      <ToastContainer/>
      <ToastEffect/>
      <Head title={head}/>
      <div className='d-flex flex-column flex-root app-root' id='kt_app_root'>
        <div className='app-page flex-column flex-column-fluid' id='kt_app_page'>
          <HeaderWrapper/>
          <div className='app-wrapper flex-column flex-row-fluid' id='kt_app_wrapper'
               style={{minHeight: 'calc(100vh - 74px)'}}>
            <Sidebar/>
            <div className='app-main flex-column flex-row-fluid' id='kt_app_main'>
              <div className='d-flex flex-column flex-column-fluid'>
                {children}
              </div>
              <FooterWrapper/>
            </div>
          </div>
        </div>
      </div>
      <ScrollTop/>
    </PageDataProvider>

  )
}

