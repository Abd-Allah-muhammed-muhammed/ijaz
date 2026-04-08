import {useIntl} from 'react-intl'
import {SidebarMenuItem} from './SidebarMenuItem'
import useActiveRoute from "@/hooks/use-active-route";
import HomeController from "@/actions/App/Http/Controllers/Provider/HomeController";
import ChatController from "@/actions/App/Http/Controllers/Provider/ChatController";
import {SidebarMenuItemWithSub} from "@/_metronic/layout/components/sidebar/sidebar-menu/SidebarMenuItemWithSub";
import OrderController from "@/actions/App/Http/Controllers/Provider/OrderController";
import TopUpController from "@/actions/App/Http/Controllers/Provider/TopUpController";
import AuthController from "@/actions/App/Http/Controllers/Provider/AuthController";
import WithdrawController from '@/actions/App/Http/Controllers/Provider/WithdrawController';
import { useTranslation } from 'react-i18next';

const SidebarMenuMain = () => {
  const intl = useIntl()
  const {matchUrl, matchComponents} = useActiveRoute();
  const { t } = useTranslation();
  return (
    <>
      <SidebarMenuItem
        isActive={matchUrl(HomeController.url())}
        to={HomeController.url()}
        icon='element-11'
        title={t('dashboard')}
        fontIcon='bi-app-indicator'
      />
      <div className='menu-item'>
        <div className='menu-content pt-8 pb-2'>
          <span className='menu-section text-muted text-uppercase fs-8 ls-1'>{t('orders')}</span>
        </div>
      </div>

      <SidebarMenuItem
        to={OrderController.new().url}
        title={t('new_orders')}
        icon='handcart'
        isActive={matchUrl(OrderController.new().url)}
      />
      <SidebarMenuItem
        to={OrderController.index().url}
        title={t('list')}
        isActive={matchUrl(OrderController.index().url)}
        icon='search-list'

      />
      <SidebarMenuItem
        to={OrderController.offers().url}
        title={t('offers')}
        isActive={matchUrl(OrderController.offers().url)}
        icon='office-bag'

      />

      <div className='menu-item'>
        <div className='menu-content pt-8 pb-2'>
          <span className='menu-section text-muted text-uppercase fs-8 ls-1'>{t('finance')}</span>
        </div>
      </div>
      <SidebarMenuItem
        isActive={matchUrl(AuthController.statements().url)}
        to={AuthController.statements().url}
        title={t('wallet')}
        fontIcon='bi-chat-left'
        icon='wallet'
      />
      <SidebarMenuItem
        isActive={matchUrl(TopUpController.index().url)}
        to={TopUpController.index().url}
        title={t('top_up_requests')}
        fontIcon='bi-chat-left'
        icon='two-credit-cart'
      />
      <SidebarMenuItem
        isActive={matchUrl(WithdrawController.index().url)}
        to={WithdrawController.index().url}
        title={t('withdraw_requests')}
        fontIcon='bi-chat-left'
        icon='two-credit-cart'
      />
      <div className='menu-item'>
        <div className='menu-content pt-8 pb-2'>
          <span className='menu-section text-muted text-uppercase fs-8 ls-1'>{t('communications')}</span>
        </div>
      </div>
      <SidebarMenuItem
        isActive={matchUrl(ChatController.index().url)}
        to={ChatController.index().url}
        title={t('chat')}
        fontIcon='bi-chat-left'
        icon='message-text-2'
      />
    </>
  )
}

export {SidebarMenuMain}
