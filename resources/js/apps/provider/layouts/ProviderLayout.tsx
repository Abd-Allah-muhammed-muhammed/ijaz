import {toast} from 'sonner'
import {HeaderWrapper} from '@/apps/provider/layouts/header'
import {ScrollTop} from '@/apps/provider/layouts/scroll-top'
import {FooterWrapper} from '@/apps/provider/layouts/footer'
import {Sidebar} from '@/apps/provider/layouts/sidebar'
import {PageDataProvider} from '@/apps/provider/layouts/core'
import {ReactNode, useEffect, useRef, useState} from "react";
import {Head, router, usePage} from "@inertiajs/react";
import {KTIcon, reInitMenu} from "@/vendor/metronic/helpers";
import ToastEffect from "@/shared/components/toaster/toast-effect";
import ToastContainer from "@/shared/components/toaster/toast-container";
import {Conversation, Order, Provider} from "@/shared/types/models";
import {makeOffline, makeOnline} from "@/shared/helpers/general";
import {useRecommendedOrdersContext} from "@/store/recommend-orders-context";
import {useTranslation} from "react-i18next";
import {useConversations} from "@/store/use-chat";
import {ChatEventEnum} from "@/Enums/Chat";
import ProviderChatIndexController from "@/actions/Modules/Orders/Http/Controllers/Provider/ProviderChatIndexController";
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
  // Echo listeners mount once — keep URL / open conversation current via refs.
  const urlRef = useRef(url);
  const localeRef = useRef(locale);
  const currentConversationRef = useRef(currentConversation);
  const updateConversationRef = useRef(updateConversationForNewMessages);
  urlRef.current = url;
  localeRef.current = locale;
  currentConversationRef.current = currentConversation;
  updateConversationRef.current = updateConversationForNewMessages;

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
        const cleanUrl = urlRef.current.split('?')[0];
        const chatIndexPath = `/${localeRef.current}${ProviderChatIndexController.url()}`;
        const openConversation = currentConversationRef.current;

        // Always refresh the sidebar list when ChatUpdated fires — not only on
        // the chat index URL (ref avoids stale closure after navigation).
        if (chat.last_message?.sender?.socket_id === user.socket_id) {
          chat.unread_count = 0;
        }
        updateConversationRef.current(chat);

        if (cleanUrl === chatIndexPath) {
          return;
        }

        if (chat.last_message?.sender?.socket_id !== user.socket_id && chat.id !== openConversation?.id) {
          const attachmentCount = chat.last_message?.attachments_count ?? 0;
          const description = (chat.last_message?.content ?? '').trim()
            || (attachmentCount > 0 ? t('attachment') : '');

          toast.message(chat.last_message?.sender?.name, {
            description,
            id: chat.id,
            action: <Button
              variant='outline-secondary'
              className="ms-auto"
              title={t('view')}
              size='sm'
              onClick={() => {
                router.visit(ProviderChatIndexController.url({
                  query: {conversation: chat.id},
                }), {
                  preserveScroll: true,
                  replace: true,
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

